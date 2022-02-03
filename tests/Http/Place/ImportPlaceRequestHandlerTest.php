<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url as LegacyUrl;

final class ImportPlaceRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private MockObject $aggregateRepository;

    private MockObject $uuidGenerator;

    private TraceableCommandBus $commandBus;

    private MockObject $imageCollectionFactory;

    private MockObject $lockedLabelRepository;

    private MockObject $consumerSpecification;

    private MockObject $apiReader;

    private MockObject $consumerRepository;

    private ImportPlaceRequestHandler $importPlaceRequestHandler;

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->commandBus = new TraceableCommandBus();
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);
        $this->consumerSpecification = $this->createMock(ConsumerSpecificationInterface::class);
        $this->apiReader = $this->createMock(ApiKeyReaderInterface::class);
        $this->consumerRepository = $this->createMock(ConsumerReadRepositoryInterface::class);

        $this->importPlaceRequestHandler = new ImportPlaceRequestHandler(
            $this->aggregateRepository,
            $this->uuidGenerator,
            new PlaceDenormalizer(),
            new CombinedRequestBodyParser(),
            new CallableIriGenerator(fn ($placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->commandBus,
            $this->imageCollectionFactory,
            $this->lockedLabelRepository,
            $this->consumerSpecification,
            $this->apiReader,
            $this->consumerRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_imports_a_new_place_with_only_required_fields(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = [
            'name' => [
                'nl' => 'Cafe Den Hemel',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Scherpenheuvel-Zichem',
                    'postalCode' => '3271',
                    'streetAddress' => 'Hoornblaas 107',
                ],
            ],
            'calendarType' => 'permanent',
            'mainLanguage' => 'nl',
        ];

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($placeId);

        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    fn (Place $place) => $place->getAggregateRootId() === $placeId
                )
            );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($placeId)
            ->willReturn(new Labels());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(['id' => $placeId]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteCurrentOrganizer($placeId),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_updates_an_existing_place(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = [
            'name' => [
                'nl' => 'In De Hel',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Leuven',
                    'postalCode' => '3000',
                    'streetAddress' => 'Martelarenplein 1',
                ],
            ],
            'calendarType' => 'permanent',
            'mainLanguage' => 'nl',
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'We zijn nog steeds gesloten.',
                ],
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'price' => 10.5,
                    'priceCurrency' => 'EUR',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '016 10 20 30',
                ],
                'email' => [
                    'info@dehel.be',
                ],
                'url' => [
                    'https://www.dehel.be',
                ],
            ],
            'bookingInfo' => [
                'phone' => '016 10 20 30',
                'email' => 'booking@dehel.be',
                'url' => 'https://www.dehel.be/booking',
                'urlLabel' => [
                    'nl' => 'Bestel hier je tickets',
                ],
                'availabilityStarts' => '2020-05-17T22:00:00+00:00',
                'availabilityEnds' => '2028-05-17T22:00:00+00:00',
            ],
            'mediaObject' => [
                [
                    '@id' => 'https://io.uitdatabank.be/images/8b3c82d5-6cfe-442e-946c-1f4452636d61',
                    'description' => 'Feest in de Hel',
                    'copyrightHolder' => 'De Hel',
                    'inLanguage' => 'nl',
                ],
            ],
            'videos' => [
                [
                    'id' => 'b504cf44-9ab8-4641-9934-38d1cc67242c',
                    'url' => 'https://www.youtube.com/watch?v=cEItmb_a20D',
                    'embedUrl' => 'https://www.youtube.com/embed/cEItmb_a20D',
                    'language' => 'nl',
                    'copyrightHolder' => 'De Hel',
                ],
                [
                    'id' => 'c03a3e8a-0346-4d32-b2ac-4aedac49dc30',
                    'url' => 'https://vimeo.com/98765432',
                    'language' => 'nl',
                ],
            ],
            'labels' => [
                'visible_label',
            ],
            'hiddenLabels' => [
                'hidden_label',
            ],
        ];

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($placeId);

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(
                (new ImageCollection())
                    ->with(new Image(
                        new UUID('8b3c82d5-6cfe-442e-946c-1f4452636d61'),
                        MIMEType::fromSubtype('jpeg'),
                        new Description('Feest in de Hel'),
                        new CopyrightHolder('De Hel'),
                        LegacyUrl::fromNative('https://io.uitdatabank.be/images/8b3c82d5-6cfe-442e-946c-1f4452636d61.jpeg'),
                        new LegacyLanguage('nl')
                    ))
            );

        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($placeId)
            ->willReturn(new Labels());

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('placeId', $placeId)
            ->withJsonBodyFromArray($givenPlace)
            ->build('PUT');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode(['id' => $placeId]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateTitle($placeId, new LegacyLanguage('nl'), new Title('In De Hel')),
                new UpdateType($placeId, 'Yf4aZBfsUEu2NsQqsprngw'),
                new UpdateAddress(
                    $placeId,
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        Country::fromNative('BE')
                    ),
                    new LegacyLanguage('nl')
                ),
                new UpdateCalendar(
                    $placeId,
                    (new Calendar(CalendarType::PERMANENT()))
                        ->withStatus(
                            new Status(
                                StatusType::unavailable(),
                                [
                                    new StatusReason(
                                        new LegacyLanguage('nl'),
                                        'We zijn nog steeds gesloten.'
                                    ),
                                ]
                            )
                        )
                ),
                new UpdateBookingInfo(
                    $placeId,
                    new BookingInfo(
                        'https://www.dehel.be/booking',
                        new MultilingualString(
                            new LegacyLanguage('nl'),
                            new StringLiteral('Bestel hier je tickets')
                        ),
                        '016 10 20 30',
                        'booking@dehel.be',
                        new DateTimeImmutable('2020-05-17T22:00:00+00:00'),
                        new DateTimeImmutable('2028-05-17T22:00:00+00:00'),
                    )
                ),
                new UpdateContactPoint(
                    $placeId,
                    new ContactPoint(
                        ['016 10 20 30'],
                        ['info@dehel.be'],
                        ['https://www.dehel.be']
                    )
                ),
                new DeleteCurrentOrganizer($placeId),
                new DeleteTypicalAgeRange($placeId),
                new UpdatePriceInfo(
                    $placeId,
                    new PriceInfo(
                        new BasePrice(Price::fromFloat(10.5), Currency::fromNative('EUR'))
                    )
                ),
                new ImportLabels(
                    $placeId,
                    new Labels(
                        new Label(new LabelName('visible_label'), true),
                        new Label(new LabelName('hidden_label'), false)
                    )
                ),
                new ImportImages(
                    $placeId,
                    (new ImageCollection())
                        ->with(new Image(
                            new UUID('8b3c82d5-6cfe-442e-946c-1f4452636d61'),
                            MIMEType::fromSubtype('jpeg'),
                            new Description('Feest in de Hel'),
                            new CopyrightHolder('De Hel'),
                            LegacyUrl::fromNative('https://io.uitdatabank.be/images/8b3c82d5-6cfe-442e-946c-1f4452636d61.jpeg'),
                            new LegacyLanguage('nl')
                        ))
                ),
                new ImportVideos(
                    $placeId,
                    new VideoCollection(
                        (new Video(
                            'b504cf44-9ab8-4641-9934-38d1cc67242c',
                            new Url('https://www.youtube.com/watch?v=cEItmb_a20D'),
                            new Language('nl')
                        ))->withCopyrightHolder(new CopyrightHolder('De Hel')),
                        new Video(
                            'c03a3e8a-0346-4d32-b2ac-4aedac49dc30',
                            new Url('https://vimeo.com/98765432'),
                            new Language('nl')
                        ),
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_required_property_is_missing(): void
    {
        $place = [
            'foo' => 'bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (mainLanguage, name, terms, calendarType, address) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mainLanguage_is_in_an_invalid_format(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'foo',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mainLanguage',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_has_no_entries(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_name_translation_is_empty(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
                'fr' => '',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/name/fr',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_is_a_string(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => 'Example name',
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_has_an_unknown_value(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'foobar',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/calendarType',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_terms_is_empty(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'terms' => [],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'Array should have at least 1 items, 0 found'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_term_is_missing_an_id(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'label' => 'foo',
                    'domain' => 'bar',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0',
                'The required properties (id) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_term_has_an_id_that_is_not_a_string(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 1,
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    private function assertValidationErrors(array $place, array $expectedErrors): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($placeId);

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($place)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedErrors),
            fn () => $this->importPlaceRequestHandler->handle($request)
        );
    }
}
