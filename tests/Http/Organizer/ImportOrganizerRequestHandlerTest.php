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
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\Commands\UpdateContactPoint;
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

    private MockObject $aggregateRepository;
    private TraceableCommandBus $commandBus;
    private MockObject $lockedLabelRepository;
    private MockObject $uuidGenerator;
    private ImportOrganizerRequestHandler $importOrganizerRequestHandler;

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->commandBus = new TraceableCommandBus();
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);

        $this->importOrganizerRequestHandler = new ImportOrganizerRequestHandler(
            $this->aggregateRepository,
            new OrganizerDenormalizer(),
            $this->commandBus,
            $this->lockedLabelRepository,
            $this->uuidGenerator,
            new CallableIriGenerator(fn (string $id) => 'https://mock.uitdatabank.be/organizers/' . $id),
            new CombinedRequestBodyParser()
        );

        $this->commandBus->record();
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
            new RemoveAddress($organizerId),
            new ImportLabels($organizerId, new Labels()),
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('POST');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(['id' => '5829cdfb-21b1-4494-86da-f2dbd7c8d69c']),
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
        $this->expectNoLockedLabels();

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
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('organizerId', $id)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importOrganizerRequestHandler->handle($request);

        $actualCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(['id' => $id]),
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

        $this->expectNoLockedLabels();
    }

    private function expectCreateOrganizer(Organizer $expectedOrganizer): void
    {
        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Organizer $organizer) use ($expectedOrganizer) {
                return $expectedOrganizer->getAggregateRootId() === $organizer->getAggregateRootId();
            }));
    }

    private function expectNoLockedLabels(): void
    {
        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Labels());
    }
}
