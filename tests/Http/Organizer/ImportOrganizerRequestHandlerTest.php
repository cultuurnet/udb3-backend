<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\MediaObjectRepository;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Images;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Commands\DeleteDescription;
use CultuurNet\UDB3\Organizer\Commands\DeleteEducationalDescription;
use CultuurNet\UDB3\Organizer\Commands\ImportImages;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Organizer\Commands\UpdateDescription;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\Commands\UpdateTitle;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\Organizer;
use CultuurNet\UDB3\Organizer\Organizer as OrganizerAggregate;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImportOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    private const EXISTING_IMAGE_ID = '6b547d1e-a2d9-493c-a8e6-d8eb35984390';

    private MockObject $aggregateRepository;
    private TraceableCommandBus $commandBus;
    private MockObject $uuidGenerator;
    private ImportOrganizerRequestHandler $importOrganizerRequestHandler;

    private array $mediaObjects;

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->commandBus = new TraceableCommandBus();
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $mediaObjectRepository = $this->createMock(MediaObjectRepository::class);

        $this->importOrganizerRequestHandler = new ImportOrganizerRequestHandler(
            $this->aggregateRepository,
            $this->commandBus,
            $this->uuidGenerator,
            new CallableIriGenerator(fn (string $id) => 'https://mock.uitdatabank.be/organizers/' . $id),
            new CombinedRequestBodyParser(
                new LegacyOrganizerRequestBodyParser(),
                RemoveEmptyArraysRequestBodyParser::createForOrganizers(),
                ImagesPropertyPolyfillRequestBodyParser::createForOrganizers(
                    new CallableIriGenerator(fn (string $id) => 'https://io.uitdatabank.dev/images/' . $id),
                    $mediaObjectRepository
                )
            )
        );

        $mediaObjectRepository->expects($this->any())
            ->method('load')
            ->willReturnCallback(
                function (string $id): MediaObject {
                    if (!isset($this->mediaObjects[$id])) {
                        throw new AggregateNotFoundException();
                    }
                    return $this->mediaObjects[$id];
                }
            );

        $this->mockMediaObject(self::EXISTING_IMAGE_ID, 'I exist', 'John Doe', 'en');

        $this->commandBus->record();
    }

    private function mockMediaObject(string $id, string $description, string $copyrightHolder, string $language): void
    {
        $mediaObject = $this->createMock(MediaObject::class);

        $mediaObject
            ->method('getDescription')
            ->willReturn(new ImageDescription($description));

        $mediaObject
            ->method('getCopyrightHolder')
            ->willReturn(new CopyrightHolder($copyrightHolder));

        $mediaObject
            ->method('getLanguage')
            ->willReturn(new Language($language));

        $this->mediaObjects[$id] = $mediaObject;
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_with_minimal_info_and_without_id(): void
    {
        $organizerId = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($organizerId);

        $given = $this->getOrganizerData();

        $this->expectOrganizerDoesNotExist($organizerId);

        $this->expectCreateOrganizer(
            OrganizerAggregate::create(
                $organizerId,
                new Language('nl'),
                new Url('https://www.mock-organizer.be'),
                new Title('Mock organizer')
            )
        );

        $expectedCommands = [
            new UpdateContactPoint($organizerId, new ContactPoint()),
            new DeleteDescription($organizerId, new Language('nl')),
            new DeleteDescription($organizerId, new Language('fr')),
            new DeleteDescription($organizerId, new Language('de')),
            new DeleteDescription($organizerId, new Language('en')),
            new DeleteEducationalDescription($organizerId, new Language('nl')),
            new DeleteEducationalDescription($organizerId, new Language('fr')),
            new DeleteEducationalDescription($organizerId, new Language('de')),
            new DeleteEducationalDescription($organizerId, new Language('en')),
            new RemoveAddress($organizerId),
            new ImportLabels($organizerId, new Labels()),
            new ImportImages($organizerId, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('POST');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'organizerId' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'url' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_returns_200_OK_instead_of_201_Created_for_new_organizers_if_using_old_imports_path(): void
    {
        $organizerId = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($organizerId);

        $given = $this->getOrganizerData();

        $this->expectOrganizerDoesNotExist($organizerId);

        $this->expectCreateOrganizer(
            OrganizerAggregate::create(
                $organizerId,
                new Language('nl'),
                new Url('https://www.mock-organizer.be'),
                new Title('Mock organizer')
            )
        );

        $expectedCommands = [
            new UpdateContactPoint($organizerId, new ContactPoint()),
            new DeleteDescription($organizerId, new Language('nl')),
            new DeleteDescription($organizerId, new Language('fr')),
            new DeleteDescription($organizerId, new Language('de')),
            new DeleteDescription($organizerId, new Language('en')),
            new DeleteEducationalDescription($organizerId, new Language('nl')),
            new DeleteEducationalDescription($organizerId, new Language('fr')),
            new DeleteEducationalDescription($organizerId, new Language('de')),
            new DeleteEducationalDescription($organizerId, new Language('en')),
            new RemoveAddress($organizerId),
            new ImportLabels($organizerId, new Labels()),
            new ImportImages($organizerId, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/imports/organizers')
            ->withJsonBodyFromArray($given)
            ->build('POST');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_ignores_empty_list_properties_and_null_values(): void
    {
        $organizerId = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($organizerId);

        $given = $this->getOrganizerData() + [
            'labels' => [null],
            'hiddenLabels' => null,
            'images' => [],
            'contactPoint' => (object) [
                'phone' => null,
            ],
        ];

        $this->expectOrganizerDoesNotExist($organizerId);

        $this->expectCreateOrganizer(
            OrganizerAggregate::create(
                $organizerId,
                new Language('nl'),
                new Url('https://www.mock-organizer.be'),
                new Title('Mock organizer')
            )
        );

        $expectedCommands = [
            new UpdateContactPoint($organizerId, new ContactPoint()),
            new DeleteDescription($organizerId, new Language('nl')),
            new DeleteDescription($organizerId, new Language('fr')),
            new DeleteDescription($organizerId, new Language('de')),
            new DeleteDescription($organizerId, new Language('en')),
            new DeleteEducationalDescription($organizerId, new Language('nl')),
            new DeleteEducationalDescription($organizerId, new Language('fr')),
            new DeleteEducationalDescription($organizerId, new Language('de')),
            new DeleteEducationalDescription($organizerId, new Language('en')),
            new RemoveAddress($organizerId),
            new ImportLabels($organizerId, new Labels()),
            new ImportImages($organizerId, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('POST');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'organizerId' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'url' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_imports_a_complete_organizer_with_an_existing_id(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = $this->getOrganizerData() +
            [
                'description' => [
                    'nl' => 'Dutch description',
                    'fr' => 'French description',
                    'de' => 'German description',
                    'en' => 'English description',
                ],
                'educationalDescription' => [
                    'nl' => 'Dutch educational description',
                    'fr' => 'French educational description',
                    'de' => 'German educational description',
                    'en' => 'English educational description',
                ],
                'address' => [
                    'nl' => [
                        'streetAddress' => 'Henegouwenkaai 41-43',
                        'postalCode' => '1080',
                        'addressLocality' => 'Brussel',
                        'addressCountry' => 'BE',
                    ],
                    'fr' => [
                        'streetAddress' => 'Quai du Hainaut 41-43',
                        'postalCode' => '1080',
                        'addressLocality' => 'Bruxelles',
                        'addressCountry' => 'BE',
                    ],
                ],
                'contactPoint' => [
                    'phone' => ['123'],
                    'email' => ['mock@publiq.be'],
                    'url' => ['https://www.publiq.be'],
                ],
                'labels' => ['foo'],
                'hiddenLabels' => ['bar'],
            ];

        $given['name']['fr'] = 'French name';

        $this->expectOrganizerExists($id);

        $expectedCommands = [
            new UpdateTitle(
                $id,
                new Title('Mock organizer'),
                new Language('nl')
            ),
            new UpdateWebsite(
                $id,
                new Url('https://www.mock-organizer.be')
            ),
            new UpdateContactPoint(
                $id,
                new ContactPoint(
                    new TelephoneNumbers(new TelephoneNumber('123')),
                    new EmailAddresses(new EmailAddress('mock@publiq.be')),
                    new Urls(new Url('https://www.publiq.be'))
                )
            ),
            new UpdateDescription(
                $id,
                new Description('Dutch description'),
                new Language('nl')
            ),
            new UpdateDescription(
                $id,
                new Description('French description'),
                new Language('fr')
            ),
            new UpdateDescription(
                $id,
                new Description('German description'),
                new Language('de')
            ),
            new UpdateDescription(
                $id,
                new Description('English description'),
                new Language('en')
            ),
            new UpdateEducationalDescription(
                $id,
                new Description('Dutch educational description'),
                new Language('nl')
            ),
            new UpdateEducationalDescription(
                $id,
                new Description('French educational description'),
                new Language('fr')
            ),
            new UpdateEducationalDescription(
                $id,
                new Description('German educational description'),
                new Language('de')
            ),
            new UpdateEducationalDescription(
                $id,
                new Description('English educational description'),
                new Language('en')
            ),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    new CountryCode('BE')
                ),
                new Language('nl')
            ),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    new CountryCode('BE')
                ),
                new Language('fr')
            ),
            new UpdateTitle(
                $id,
                new Title('French name'),
                new Language('fr')
            ),
            new ImportLabels(
                $id,
                new Labels(
                    new Label(new LabelName('foo'), true),
                    new Label(new LabelName('bar'), false),
                )
            ),
            new ImportImages($id, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => $id,
                    'organizerId' => $id,
                    'url' => 'https://mock.uitdatabank.be/organizers/' . $id,
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_with_missing_contactPoint_fields(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = $this->getOrganizerData() +
            [
                'contactPoint' => [
                    'email' => ['mock@publiq.be'],
                ],
            ];

        $this->expectOrganizerExists($id);

        $expectedCommands = [
            new UpdateTitle(
                $id,
                new Title('Mock organizer'),
                new Language('nl')
            ),
            new UpdateWebsite(
                $id,
                new Url('https://www.mock-organizer.be')
            ),
            new UpdateContactPoint(
                $id,
                new ContactPoint(
                    new TelephoneNumbers(),
                    new EmailAddresses(new EmailAddress('mock@publiq.be')),
                    new Urls()
                )
            ),
            new DeleteDescription($id, new Language('nl')),
            new DeleteDescription($id, new Language('fr')),
            new DeleteDescription($id, new Language('de')),
            new DeleteDescription($id, new Language('en')),
            new DeleteEducationalDescription($id, new Language('nl')),
            new DeleteEducationalDescription($id, new Language('fr')),
            new DeleteEducationalDescription($id, new Language('de')),
            new DeleteEducationalDescription($id, new Language('en')),
            new RemoveAddress($id),
            new ImportLabels($id, new Labels()),
            new ImportImages($id, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => $id,
                    'organizerId' => $id,
                    'url' => 'https://mock.uitdatabank.be/organizers/' . $id,
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_with_images(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $this->mockMediaObject(
            '1a11c93c-3fd6-4d59-abb9-e8df724a3894',
            'beschrijving1',
            'copyrightholder1',
            'nl'
        );

        $this->mockMediaObject(
            '67171a6a-031d-4040-88aa-556e85165e33',
            'beschrijving2',
            'copyrightholder2',
            'nl'
        );

        $this->mockMediaObject(
            '5ab13b22-a913-4c8e-aa3b-a32279a771da',
            'beschrijving3',
            'copyrightholder3',
            'nl'
        );

        $given = $this->getOrganizerData() +
            [
                'images' => [
                    [
                        '@id' => 'https://io.uitdatabank.dev/images/1a11c93c-3fd6-4d59-abb9-e8df724a3894',
                    ],
                    [
                        'id' => '67171a6a-031d-4040-88aa-556e85165e33',
                        'description' => 'overwritten!',
                    ],
                    [
                        'id' => '5ab13b22-a913-4c8e-aa3b-a32279a771da',
                        'copyrightHolder' => 'overwritten!',
                        'inLanguage' => 'en',
                    ],
                ],
            ];

        $this->expectOrganizerExists($id);

        $expectedCommands = [
            new UpdateTitle(
                $id,
                new Title('Mock organizer'),
                new Language('nl')
            ),
            new UpdateWebsite(
                $id,
                new Url('https://www.mock-organizer.be')
            ),
            new UpdateContactPoint($id, new ContactPoint(new TelephoneNumbers(), new EmailAddresses(), new Urls())),
            new DeleteDescription($id, new Language('nl')),
            new DeleteDescription($id, new Language('fr')),
            new DeleteDescription($id, new Language('de')),
            new DeleteDescription($id, new Language('en')),
            new DeleteEducationalDescription($id, new Language('nl')),
            new DeleteEducationalDescription($id, new Language('fr')),
            new DeleteEducationalDescription($id, new Language('de')),
            new DeleteEducationalDescription($id, new Language('en')),
            new RemoveAddress($id),
            new ImportLabels($id, new Labels()),
            new ImportImages(
                $id,
                new Images(
                    new Image(
                        new Uuid('1a11c93c-3fd6-4d59-abb9-e8df724a3894'),
                        new Language('nl'),
                        new Description('beschrijving1'),
                        new CopyrightHolder('copyrightholder1')
                    ),
                    new Image(
                        new Uuid('67171a6a-031d-4040-88aa-556e85165e33'),
                        new Language('nl'),
                        new Description('overwritten!'),
                        new CopyrightHolder('copyrightholder2')
                    ),
                    new Image(
                        new Uuid('5ab13b22-a913-4c8e-aa3b-a32279a771da'),
                        new Language('en'),
                        new Description('beschrijving3'),
                        new CopyrightHolder('overwritten!')
                    )
                )
            ),
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => $id,
                    'organizerId' => $id,
                    'url' => 'https://mock.uitdatabank.be/organizers/' . $id,
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_from_legacy_schema_with_only_required_properties(): void
    {
        $organizerId = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($organizerId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Mock organizer',
            'website' => 'https://www.mock-organizer.be',
        ];

        $this->expectOrganizerDoesNotExist($organizerId);

        $this->expectCreateOrganizer(
            OrganizerAggregate::create(
                $organizerId,
                new Language('nl'),
                new Url('https://www.mock-organizer.be'),
                new Title('Mock organizer')
            )
        );

        $expectedCommands = [
            new UpdateContactPoint($organizerId, new ContactPoint()),
            new DeleteDescription($organizerId, new Language('nl')),
            new DeleteDescription($organizerId, new Language('fr')),
            new DeleteDescription($organizerId, new Language('de')),
            new DeleteDescription($organizerId, new Language('en')),
            new DeleteEducationalDescription($organizerId, new Language('nl')),
            new DeleteEducationalDescription($organizerId, new Language('fr')),
            new DeleteEducationalDescription($organizerId, new Language('de')),
            new DeleteEducationalDescription($organizerId, new Language('en')),
            new RemoveAddress($organizerId),
            new ImportLabels($organizerId, new Labels()),
            new ImportImages($organizerId, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('POST');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'organizerId' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'url' => 'https://mock.uitdatabank.be/organizers/5829cdfb-21b1-4494-86da-f2dbd7c8d69c',
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_imports_an_organizer_from_legacy_schema_with_all_properties_from_old_create_request(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Mock organizer',
            'website' => 'https://www.mock-organizer.be',
            'address' => [
                'streetAddress' => 'Henegouwenkaai 41-43',
                'postalCode' => '1080',
                'addressLocality' => 'Brussel',
                'addressCountry' => 'BE',
            ],
            'contact' => [
                ['type' => 'phone', 'value' => '123'],
                ['type' => 'email', 'value' => 'mock@publiq.be'],
                ['type' => 'url', 'value' => 'https://www.publiq.be'],
            ],
        ];

        $this->expectOrganizerExists($id);

        $expectedCommands = [
            new UpdateTitle(
                $id,
                new Title('Mock organizer'),
                new Language('nl')
            ),
            new UpdateWebsite(
                $id,
                new Url('https://www.mock-organizer.be')
            ),
            new UpdateContactPoint(
                $id,
                new ContactPoint(
                    new TelephoneNumbers(new TelephoneNumber('123')),
                    new EmailAddresses(new EmailAddress('mock@publiq.be')),
                    new Urls(new Url('https://www.publiq.be'))
                )
            ),
            new DeleteDescription($id, new Language('nl')),
            new DeleteDescription($id, new Language('fr')),
            new DeleteDescription($id, new Language('de')),
            new DeleteDescription($id, new Language('en')),
            new DeleteEducationalDescription($id, new Language('nl')),
            new DeleteEducationalDescription($id, new Language('fr')),
            new DeleteEducationalDescription($id, new Language('de')),
            new DeleteEducationalDescription($id, new Language('en')),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    new CountryCode('BE')
                ),
                new Language('nl')
            ),
            new ImportLabels($id, new Labels()),
            new ImportImages($id, new Images()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(
                [
                    'id' => $id,
                    'organizerId' => $id,
                    'url' => 'https://mock.uitdatabank.be/organizers/' . $id,
                    'commandId' => Uuid::NIL,
                ]
            ),
            $response->getBody()->getContents()
        );
        $this->assertEquals($expectedCommands, $actualCommands);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_an_existing_uuid_of_an_event_or_place_is_given(): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $given = $this->getOrganizerData();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->expectOrganizerDoesNotExist($id);

        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->willThrowException(
                DBALEventStoreException::create(
                    $this->createMock(UniqueConstraintViolationException::class)
                )
            );

        $this->assertCallableThrowsApiProblem(
            ApiProblem::resourceIdAlreadyInUse('5829cdfb-21b1-4494-86da-f2dbd7c8d69c'),
            fn () => $this->importOrganizerRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider schemaErrorsDataProvider
     */
    public function it_throws_an_api_problem_if_schema_errors_occur(array $given, array $schemaErrors): void
    {
        $id = '5829cdfb-21b1-4494-86da-f2dbd7c8d69c';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->expectOrganizerDoesNotExist($id);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$schemaErrors),
            fn () => $this->importOrganizerRequestHandler->handle($request)
        );
    }

    public function schemaErrorsDataProvider(): array
    {
        return [
            'required_properties_missing' => [
                'given' => [
                    'foo' => 'bar',
                ],
                'schemaErrors' => [
                    new SchemaError('/', 'The required properties (mainLanguage, url, name) are missing'),
                ],
            ],
            'mainLanguage_invalid' => [
                'given' => [
                    'mainLanguage' => 'foo',
                    'name' => [
                        'nl' => 'Test',
                    ],
                    'url' => 'https://www.organizer.be',
                ],
                'schemaErrors' => [
                    new SchemaError('/mainLanguage', 'The data should match one item from enum'),
                ],
            ],
            'url_invalid' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => [
                        'nl' => 'Test',
                    ],
                    'url' => 'foobar',
                ],
                'schemaErrors' => [
                    new SchemaError('/url', 'The data must match the \'uri\' format'),
                ],
            ],
            'name_invalid' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => false,
                    'url' => 'https://www.organizer.be',
                ],
                'schemaErrors' => [
                    new SchemaError('/name', 'The data (boolean) must match the type: object'),
                ],
            ],
            'name_missing_value_for_mainLanguage' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['fr' => 'Test'],
                    'url' => 'https://www.organizer.be',
                ],
                'schemaErrors' => [
                    new SchemaError('/name', 'A value in the mainLanguage (nl) is required.'),
                ],
            ],
            'name_empty' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => [
                        'nl' => '',
                        'fr' => '',
                        'de' => '',
                        'en' => '',
                    ],
                    'url' => 'https://www.organizer.be',
                ],
                'schemaErrors' => [
                    new SchemaError('/name/nl', 'Minimum string length is 1, found 0'),
                    new SchemaError('/name/fr', 'Minimum string length is 1, found 0'),
                    new SchemaError('/name/de', 'Minimum string length is 1, found 0'),
                    new SchemaError('/name/en', 'Minimum string length is 1, found 0'),
                ],
            ],
            'name_whitespaces' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => [
                        'nl' => '  ',
                        'fr' => '  ',
                        'de' => '  ',
                        'en' => '  ',
                    ],
                    'url' => 'https://www.organizer.be',
                ],
                'schemaErrors' => [
                    new SchemaError('/name/nl', 'The string should match pattern: \S'),
                    new SchemaError('/name/fr', 'The string should match pattern: \S'),
                    new SchemaError('/name/de', 'The string should match pattern: \S'),
                    new SchemaError('/name/en', 'The string should match pattern: \S'),
                ],
            ],
            'address_invalid' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => 'foo',
                ],
                'schemaErrors' => [
                    new SchemaError('/address', 'The data (string) must match the type: object'),
                ],
            ],
            'address_missing_value_for_mainLanguage' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => [
                        'fr' => [
                            'addressCountry' => 'BE',
                            'addressLocality' => 'Leuven',
                            'postalCode' => '3000',
                            'streetAddress' => 'Mockstraat 1',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/address', 'A value in the mainLanguage (nl) is required.'),
                ],
            ],
            'address_properties_missing' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => ['nl' => (object) []],
                ],
                'schemaErrors' => [
                    new SchemaError('/address/nl', 'The required properties (addressCountry, addressLocality, postalCode, streetAddress) are missing'),
                ],
            ],
            'address_properties_empty' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => [
                        'nl' => [
                            'addressCountry' => '',
                            'addressLocality' => '',
                            'postalCode' => '',
                            'streetAddress' => '',
                        ],
                        'fr' => [
                            'addressCountry' => '',
                            'addressLocality' => '',
                            'postalCode' => '',
                            'streetAddress' => '',
                        ],
                        'en' => [
                            'addressCountry' => '',
                            'addressLocality' => '',
                            'postalCode' => '',
                            'streetAddress' => '',
                        ],
                        'de' => [
                            'addressCountry' => '',
                            'addressLocality' => '',
                            'postalCode' => '',
                            'streetAddress' => '',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/address/nl/addressCountry', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/nl/addressLocality', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/nl/postalCode', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/nl/streetAddress', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/fr/addressCountry', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/fr/addressLocality', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/fr/postalCode', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/fr/streetAddress', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/de/addressCountry', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/de/addressLocality', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/de/postalCode', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/de/streetAddress', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/en/addressCountry', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/en/addressLocality', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/en/postalCode', 'Minimum string length is 1, found 0'),
                    new SchemaError('/address/en/streetAddress', 'Minimum string length is 1, found 0'),
                ],
            ],
            'address_properties_whitespace' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => [
                        'nl' => [
                            'addressCountry' => 'BE',
                            'addressLocality' => '   ',
                            'postalCode' => '   ',
                            'streetAddress' => '   ',
                        ],
                        'fr' => [
                            'addressCountry' => 'BE',
                            'addressLocality' => '   ',
                            'postalCode' => '   ',
                            'streetAddress' => '   ',
                        ],
                        'en' => [
                            'addressCountry' => 'BE',
                            'addressLocality' => '   ',
                            'postalCode' => '   ',
                            'streetAddress' => '   ',
                        ],
                        'de' => [
                            'addressCountry' => 'BE',
                            'addressLocality' => '   ',
                            'postalCode' => '   ',
                            'streetAddress' => '   ',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/address/nl/addressLocality', 'The string should match pattern: \S'),
                    new SchemaError('/address/nl/postalCode', 'The string should match pattern: \S'),
                    new SchemaError('/address/nl/streetAddress', 'The string should match pattern: \S'),
                    new SchemaError('/address/fr/addressLocality', 'The string should match pattern: \S'),
                    new SchemaError('/address/fr/postalCode', 'The string should match pattern: \S'),
                    new SchemaError('/address/fr/streetAddress', 'The string should match pattern: \S'),
                    new SchemaError('/address/de/addressLocality', 'The string should match pattern: \S'),
                    new SchemaError('/address/de/postalCode', 'The string should match pattern: \S'),
                    new SchemaError('/address/de/streetAddress', 'The string should match pattern: \S'),
                    new SchemaError('/address/en/addressLocality', 'The string should match pattern: \S'),
                    new SchemaError('/address/en/postalCode', 'The string should match pattern: \S'),
                    new SchemaError('/address/en/streetAddress', 'The string should match pattern: \S'),
                ],
            ],
            'address_countries_too_long' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'address' => [
                        'nl' => [
                            'addressCountry' => 'BEE',
                            'addressLocality' => 'Leuven',
                            'postalCode' => '3000',
                            'streetAddress' => 'Mockstraat 1',
                        ],
                        'fr' => [
                            'addressCountry' => 'BEE',
                            'addressLocality' => 'Leuven',
                            'postalCode' => '3000',
                            'streetAddress' => 'Mockstraat 1',
                        ],
                        'en' => [
                            'addressCountry' => 'BEE',
                            'addressLocality' => 'Leuven',
                            'postalCode' => '3000',
                            'streetAddress' => 'Mockstraat 1',
                        ],
                        'de' => [
                            'addressCountry' => 'BEE',
                            'addressLocality' => 'Leuven',
                            'postalCode' => '3000',
                            'streetAddress' => 'Mockstraat 1',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/address/nl/addressCountry', 'Maximum string length is 2, found 3'),
                    new SchemaError('/address/fr/addressCountry', 'Maximum string length is 2, found 3'),
                    new SchemaError('/address/de/addressCountry', 'Maximum string length is 2, found 3'),
                    new SchemaError('/address/en/addressCountry', 'Maximum string length is 2, found 3'),
                ],
            ],
            'contactPoint_properties_invalid' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'contactPoint' => (object) [
                        'phone' => '123',
                        'url' => 'https://www.organizer.be/contact',
                        'email' => 'info@organizer.be',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/contactPoint/phone', 'The data (string) must match the type: array'),
                    new SchemaError('/contactPoint/email', 'The data (string) must match the type: array'),
                    new SchemaError('/contactPoint/url', 'The data (string) must match the type: array'),
                ],
            ],
            'contactPoint_properties_invalid_array_values' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'contactPoint' => (object) [
                        'phone' => [''],
                        'url' => ['foobar'],
                        'email' => ['foobar'],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/contactPoint/phone/0', 'Minimum string length is 1, found 0'),
                    new SchemaError('/contactPoint/email/0', 'The data must match the \'email\' format'),
                    new SchemaError('/contactPoint/url/0', 'The data must match the \'uri\' format'),
                ],
            ],
            'contactPoint_properties_whitespace' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'contactPoint' => (object) [
                        'phone' => ['   '],
                        'url' => [],
                        'email' => [],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/contactPoint/phone/0', 'The string should match pattern: \S'),
                ],
            ],
            'description_missing_value_for_mainLanguage' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'description' => [
                        'fr' => 'Parlez-vous français?',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/description', 'A value in the mainLanguage (nl) is required.'),
                ],
            ],
            'description_empty_value' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'description' => [
                        'nl' => '',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/description/nl', 'Minimum string length is 1, found 0'),
                ],
            ],
            'description_whitespace' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'description' => [
                        'nl' => '   ',
                        'fr' => '   ',
                        'de' => '   ',
                        'en' => '   ',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/description/nl', 'The string should match pattern: \S'),
                    new SchemaError('/description/fr', 'The string should match pattern: \S'),
                    new SchemaError('/description/de', 'The string should match pattern: \S'),
                    new SchemaError('/description/en', 'The string should match pattern: \S'),
                ],
            ],
            'educational_description_missing_value_for_mainLanguage' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'educationalDescription' => [
                        'fr' => 'Parlez-vous français?',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/educationalDescription', 'A value in the mainLanguage (nl) is required.'),
                ],
            ],
            'educational_description_empty_value' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'educationalDescription' => [
                        'nl' => '',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/educationalDescription/nl', 'Minimum string length is 1, found 0'),
                ],
            ],
            'educational_description_whitespace' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'educationalDescription' => [
                        'nl' => '   ',
                        'fr' => '   ',
                        'de' => '   ',
                        'en' => '   ',
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/educationalDescription/nl', 'The string should match pattern: \S'),
                    new SchemaError('/educationalDescription/fr', 'The string should match pattern: \S'),
                    new SchemaError('/educationalDescription/de', 'The string should match pattern: \S'),
                    new SchemaError('/educationalDescription/en', 'The string should match pattern: \S'),
                ],
            ],
            'images_missing_id' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'images' => [
                        [
                            'contentUrl' => 'https://images.uitdatabank.be/546a90cd-a238-417b-aa98-1b6c50c1345c.jpeg',
                            'thumbnailUrl' => 'https://images.uitdatabank.be/546a90cd-a238-417b-aa98-1b6c50c1345c.jpeg',
                            'inLanguage' => 'en',
                            'description' => 'Bla',
                            'copyrightHolder' => 'foo',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/images/0', 'The required properties (@id) are missing'),
                ],
            ],
            'images_do_not_exist' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/2a079b5f-e84d-4152-96a9-2e41631f5ca3',
                        ],
                        [
                            '@id' => 'https://io.uitdatabank.dev/images/invalid',
                        ],
                        [
                            '@id' => 'https://www.google.com',
                        ],
                        [
                            'id' => '115b61c4-1cdd-46c4-8006-9099725a6211',
                        ],
                        [
                            'id' => 'invalid',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/images/0/@id', 'Image with @id "https://io.uitdatabank.dev/images/2a079b5f-e84d-4152-96a9-2e41631f5ca3" (id "2a079b5f-e84d-4152-96a9-2e41631f5ca3") does not exist.'),
                    new SchemaError('/images/1/@id', 'Image with @id "https://io.uitdatabank.dev/images/invalid" does not exist.'),
                    new SchemaError('/images/2/@id', 'Image with @id "https://www.google.com" does not exist.'),
                    new SchemaError('/images/3/@id', 'Image with @id "https://io.uitdatabank.dev/images/115b61c4-1cdd-46c4-8006-9099725a6211" (id "115b61c4-1cdd-46c4-8006-9099725a6211") does not exist.'),
                    new SchemaError('/images/4/@id', 'Image with @id "https://io.uitdatabank.dev/images/invalid" does not exist.'),
                ],
            ],
            'images_properties_whitespace' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'images' => [
                        [
                            '@id' => 'https://io.uitdatabank.be/images/' . self::EXISTING_IMAGE_ID,
                            'contentUrl' => 'https://images.uitdatabank.be/' . self::EXISTING_IMAGE_ID . '.jpeg',
                            'thumbnailUrl' => 'https://images.uitdatabank.be/' . self::EXISTING_IMAGE_ID . '.jpeg',
                            'id' => self::EXISTING_IMAGE_ID,
                            'inLanguage' => 'en',
                            'description' => '        ',
                            'copyrightHolder' => '      ',
                        ],
                    ],
                ],
                'schemaErrors' => [
                    new SchemaError('/images/0/description', 'The string should match pattern: \S'),
                    new SchemaError('/images/0/copyrightHolder', 'The string should match pattern: \S'),
                ],
            ],
            'labels_invalid_type' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'labels' => 'foo',
                    'hiddenLabels' => 'bar',
                ],
                'schemaErrors' => [
                    new SchemaError('/labels', 'The data (string) must match the type: array'),
                    new SchemaError('/hiddenLabels', 'The data (string) must match the type: array'),
                ],
            ],
            'labels_invalid_values' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'labels' => ['', 'waytoolong' . implode('', range(0, 255))],
                    'hiddenLabels' => ['shouldnotcontain;'],
                ],
                'schemaErrors' => [
                    new SchemaError('/labels/0', 'Minimum string length is 2, found 0'),
                    new SchemaError('/labels/1', 'Maximum string length is 255, found 668'),
                    new SchemaError('/hiddenLabels/0', 'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'),
                ],
            ],
            'labels_duplicate_in_hiddenLabels' => [
                'given' => [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Test'],
                    'url' => 'https://www.organizer.be',
                    'labels' => ['foo', 'UitPas MecHeLen'],
                    'hiddenLabels' => ['UiTPAS Mechelen'],
                ],
                'schemaErrors' => [
                    new SchemaError('/labels/1', 'Label "UitPas MecHeLen" cannot be both in labels and hiddenLabels properties.'),
                ],
            ],
        ];
    }

    private function getOrganizerData(): array
    {
        return [
            '@id' => 'incorrect',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Mock organizer',
            ],
            'url' => 'https://www.mock-organizer.be',
        ];
    }

    private function expectOrganizerExists(string $organizerId): void
    {
        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($organizerId)
            ->willReturn($this->createMock(OrganizerAggregate::class));
    }

    private function expectOrganizerDoesNotExist(string $organizerId): void
    {
        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($organizerId)
            ->willThrowException(new AggregateNotFoundException());
    }

    private function expectCreateOrganizer(Organizer $expectedOrganizer): void
    {
        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Organizer $organizer) use ($expectedOrganizer) {
                return $expectedOrganizer->getAggregateRootId() === $organizer->getAggregateRootId();
            }));
    }
}
