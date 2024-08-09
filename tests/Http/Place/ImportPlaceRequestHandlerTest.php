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
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\VideoCollection;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\Moderation\Publish;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Place\ReadModel\Duplicate\LookupDuplicatePlace;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use DateTimeImmutable;
use Fig\Http\Message\StatusCodeInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactoryInterface;

final class ImportPlaceRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const PLACE_ID = 'b19d4090-db47-4520-ac1a-880684357ec9';
    private const PLACE_URI = 'https://io.uitdatabank.dev/places/';

    private MockObject $aggregateRepository;

    /** @var UuidGeneratorInterface&MockObject */
    private object $uuidGenerator;

    /** @var UuidFactoryInterface&MockObject */
    private object $uuidFactory;

    private object $commandBus;

    /** @var ImageCollectionFactory&MockObject */
    private object $imageCollectionFactory;

    private ImportPlaceRequestHandler $importPlaceRequestHandler;

    private InMemoryDocumentRepository $organizerRepository;

    private function getSimplePlace(): array
    {
        return [
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
    }

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactoryInterface::class);
        $this->commandBus = new TraceableCommandBus();
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);
        $this->organizerRepository = new InMemoryDocumentRepository();
        $this->organizerRepository->save(new JsonDocument('5cf42d51-3a4f-46f0-a8af-1cf672be8c84', '{}'));

        $this->importPlaceRequestHandler = new ImportPlaceRequestHandler(
            $this->aggregateRepository,
            $this->uuidGenerator,
            $this->getPlaceDenormalizer(),
            $this->getRequestBodyParser(),
            new CallableIriGenerator(fn ($placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->commandBus,
            $this->imageCollectionFactory,
            true,
            $this->createMock(LookupDuplicatePlace::class),
            $this->organizerRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_imports_a_new_place_with_only_required_fields(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = $this->getSimplePlace();

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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_returns_200_OK_instead_of_201_Created_for_new_places_if_using_old_imports_path(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = $this->getSimplePlace();

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

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/imports/places')
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_ignores_empty_list_properties_and_null_values(): void
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
            'labels' => [],
            'hiddenLabels' => [null],
            'mediaObject' => [],
            'priceInfo' => [],
            'openingHours' => [],
            'videos' => [],
            'contactPoint' => [
                'email' => [null],
                'phone' => null,
            ],
            'bookingInfo' => null,
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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_imports_a_new_place_and_publishes_it_if_workflowStatus_ready_for_validation(): void
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
            'workflowStatus' => 'READY_FOR_VALIDATION',
        ];

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($placeId);

        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    function (Place $place) use ($placeId): bool {
                        foreach ($place->getUncommittedEvents()->getIterator() as $domainMessage) {
                            $event = $domainMessage->getPayload();
                            if ($event instanceof Published && $event->getItemId() === $placeId) {
                                return true;
                            }
                        }
                        return false;
                    }
                )
            );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_imports_a_new_place_and_deletes_it_if_workflowStatus_deleted(): void
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
            'workflowStatus' => 'DELETED',
        ];

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($placeId);

        $this->aggregateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    function (Place $place) use ($placeId): bool {
                        return $place->getAggregateRootId() === $placeId;
                    }
                )
            );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteOffer($placeId),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_imports_a_legacy_place(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = [
            'mainLanguage' => 'nl',
            'name' => 'Cafe Den Hemel',
            'type' => [
                'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                'domain' => 'eventtype',
                'label' => 'Cultuur- of ontmoetingscentrum',
            ],
            'address' => [
                'addressCountry' => 'BE',
                'addressLocality' => 'Scherpenheuvel-Zichem',
                'postalCode' => '3271',
                'streetAddress' => 'Hoornblaas 107',
            ],
            'calendar' => [
                'calendarType' => 'periodic',
                'startDate' => '2022-02-21T23:00:00+00:00',
                'endDate' => '2028-02-22T22:59:00+00:00',
                'openingHours' => [
                    [
                        'opens' => '10:00',
                        'closes' => '20:00',
                        'dayOfWeek' => [
                            'monday',
                        ],
                    ],
                ],
            ],
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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_imports_a_legacy_place_with_missing_calendar(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $givenPlace = [
            'mainLanguage' => 'nl',
            'name' => 'Cafe Den Hemel',
            'type' => [
                'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                'domain' => 'eventtype',
                'label' => 'Cultuur- of ontmoetingscentrum',
            ],
            'address' => [
                'addressCountry' => 'BE',
                'addressLocality' => 'Scherpenheuvel-Zichem',
                'postalCode' => '3271',
                'streetAddress' => 'Hoornblaas 107',
            ],
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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($givenPlace)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
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
            'openingHours' => [
                [
                    'dayOfWeek' => [
                        'saturday',
                        'sunday',
                    ],
                    'opens' => '13:00',
                    'closes' => '23:59',
                ],
            ],
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
                        new Url('https://io.uitdatabank.be/images/8b3c82d5-6cfe-442e-946c-1f4452636d61.jpeg'),
                        new LegacyLanguage('nl')
                    ))
            );

        $videoId = \Ramsey\Uuid\Uuid::uuid4();
        $this->uuidFactory->expects($this->once())
            ->method('uuid4')
            ->willReturn($videoId);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('placeId', $placeId)
            ->withJsonBodyFromArray($givenPlace)
            ->build('PUT');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateTitle($placeId, new Language('nl'), new Title('In De Hel')),
                new UpdateType($placeId, 'Yf4aZBfsUEu2NsQqsprngw'),
                new UpdateAddress(
                    $placeId,
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    ),
                    new LegacyLanguage('nl')
                ),
                new UpdateCalendar(
                    $placeId,
                    (new Calendar(
                        CalendarType::PERMANENT(),
                        null,
                        null,
                        [],
                        [
                            new OpeningHour(
                                new OpeningTime(new Hour(13), new Minute(00)),
                                new OpeningTime(new Hour(23), new Minute(59)),
                                new DayOfWeekCollection(
                                    DayOfWeek::SATURDAY(),
                                    DayOfWeek::SUNDAY()
                                )
                            ),
                        ]
                    ))
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
                            'Bestel hier je tickets'
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
                new DeleteTypicalAgeRange($placeId),
                new UpdatePriceInfo(
                    $placeId,
                    new PriceInfo(
                        new Tariff(
                            (new TranslatedTariffName(
                                new Language('nl'),
                                new TariffName('Basistarief')
                            ))->withTranslation(
                                new Language('fr'),
                                new TariffName('Tarif de base')
                            )->withTranslation(
                                new Language('en'),
                                new TariffName('Base tariff')
                            )->withTranslation(
                                new Language('de'),
                                new TariffName('Basisrate')
                            ),
                            new Money(1050, new Currency('EUR'))
                        ),
                        new Tariffs()
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
                            new Url('https://io.uitdatabank.be/images/8b3c82d5-6cfe-442e-946c-1f4452636d61.jpeg'),
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
                            $videoId->toString(),
                            new Url('https://vimeo.com/98765432'),
                            new Language('nl')
                        ),
                    )
                ),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_updates_an_existing_place_with_organizer(): void
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
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organizers/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mainLanguage' => 'nl',
        ];

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($placeId);

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('placeId', $placeId)
            ->withJsonBodyFromArray($givenPlace)
            ->build('PUT');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateTitle($placeId, new Language('nl'), new Title('In De Hel')),
                new UpdateType($placeId, 'Yf4aZBfsUEu2NsQqsprngw'),
                new UpdateAddress(
                    $placeId,
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    ),
                    new LegacyLanguage('nl')
                ),
                new UpdateCalendar(
                    $placeId,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new UpdateOrganizer(
                    $placeId,
                    '5cf42d51-3a4f-46f0-a8af-1cf672be8c84'
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_place_with_an_organizer(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $place = [
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
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organizers/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($place)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new UpdateOrganizer($placeId, '5cf42d51-3a4f-46f0-a8af-1cf672be8c84'),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_organizer_is_not_found(): void
    {
        $place = [
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
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organizers/1c2baf22-26b7-453b-9a96-2d2fcebe2250',
            ],
            'calendarType' => 'permanent',
            'mainLanguage' => 'nl',
        ];

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $expectedErrors = [
            new SchemaError(
                '/organizer',
                'The organizer with id "1c2baf22-26b7-453b-9a96-2d2fcebe2250" was not found.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_publishes_an_existing_place_if_workflowStatus_is_ready_for_validation(): void
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
            'availableFrom' => '2030-01-01T16:00:00+01:00',
            'workflowStatus' => 'READY_FOR_VALIDATION',
        ];

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($placeId);

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('placeId', $placeId)
            ->withJsonBodyFromArray($givenPlace)
            ->build('PUT');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new Publish($placeId, new DateTimeImmutable('2030-01-01T16:00:00+01:00')),
                new UpdateTitle($placeId, new Language('nl'), new Title('In De Hel')),
                new UpdateType($placeId, 'Yf4aZBfsUEu2NsQqsprngw'),
                new UpdateAddress(
                    $placeId,
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    ),
                    new LegacyLanguage('nl')
                ),
                new UpdateCalendar(
                    $placeId,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_deletes_an_existing_place_if_workflowStatus_is_deleted(): void
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
            'workflowStatus' => 'DELETED',
        ];

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->with($placeId);

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('placeId', $placeId)
            ->withJsonBodyFromArray($givenPlace)
            ->build('PUT');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateTitle($placeId, new Language('nl'), new Title('In De Hel')),
                new UpdateType($placeId, 'Yf4aZBfsUEu2NsQqsprngw'),
                new UpdateAddress(
                    $placeId,
                    new Address(
                        new Street('Martelarenplein 1'),
                        new PostalCode('3000'),
                        new Locality('Leuven'),
                        new CountryCode('BE')
                    ),
                    new LegacyLanguage('nl')
                ),
                new UpdateCalendar(
                    $placeId,
                    new Calendar(CalendarType::PERMANENT())
                ),
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint()),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteOffer($placeId),
                new DeleteCurrentOrganizer($placeId),
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
                'The required properties (mainLanguage, name, terms, address) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_organizer_id_is_missing(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'organizer' => [
                'id' => '@id is missing',
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
                '/organizer',
                'The required properties (@id) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_organizer_id_has_invalid_format(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organisations/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
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
                '/organizer/%40id',
                'The string should match pattern: ^http[s]?:\/\/.+?\/organizer[s]?\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\/]?'
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
                'fr' => '   ',
                'en' => '',
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
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/name/en',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_not_a_string_or_array(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => 123,
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
                'The data (integer) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_is_not_translated_in_main_language(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'en' => 'Test place',
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
                '/name',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_required_fields_are_missing(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
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
                '/',
                'The required properties (startDate, endDate) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_startDate_is_before_endDate(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2020-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
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
                '/endDate',
                'endDate should not be before startDate'
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
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_startDate_or_endDate_is_malformed(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
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
                '/startDate',
                'The data must match the \'date-time\' format',
            ),
            new SchemaError(
                '/endDate',
                'The data must match the \'date-time\' format',
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_an_openingHour_misses_required_fields(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
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
                '/openingHours/0',
                'The required properties (closes) are missing'
            ),
            new SchemaError(
                '/openingHours/1',
                'The required properties (opens) are missing'
            ),
            new SchemaError(
                '/openingHours/2',
                'The required properties (dayOfWeek) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_opens_or_closes_is_malformed(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
                ],
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
                '/openingHours/0/opens',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
            new SchemaError(
                '/openingHours/0/closes',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_closes_is_before_opens(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '16:00',
                    'closes' => '08:00',
                ],
                [
                    'dayOfWeek' => ['wednesday'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
                [
                    'dayOfWeek' => ['friday'],
                    'opens' => '10:00',
                    'closes' => '08:00',
                ],
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
                '/openingHours/0/closes',
                'closes should not be before opens'
            ),
            new SchemaError(
                '/openingHours/2/closes',
                'closes should not be before opens'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_dayOfWeek_is_not_an_array(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
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
                '/openingHours/0/dayOfWeek',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_dayOfWeek_has_an_unknown_value(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.15.0.0.0',
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
                '/openingHours/0/dayOfWeek/2',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_openingHour_misses_required_fields(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
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
                '/openingHours/0',
                'The required properties (closes) are missing'
            ),
            new SchemaError(
                '/openingHours/1',
                'The required properties (opens) are missing'
            ),
            new SchemaError(
                '/openingHours/2',
                'The required properties (dayOfWeek) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_opens_or_closes_is_malformed(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
                ],
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
                '/openingHours/0/opens',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
            new SchemaError(
                '/openingHours/0/closes',
                'The string should match pattern: ^\d?\d:\d\d$'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_dayOfWeek_is_not_an_array(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
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
                '/openingHours/0/dayOfWeek',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_dayOfWeek_has_an_unknown_value(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
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
                '/openingHours/0/dayOfWeek/2',
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
                    'domain' => 'eventtype',
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
    public function it_should_throw_an_exception_if_terms_id_is_not_known(): void
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
                    'id' => '1',
                    'label' => 'foo',
                    'domain' => 'facilities',
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
                'The term 1 does not exist or is not supported'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_terms_has_more_than_one_event_types(): void
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
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '0.15.0.0.0',
                    'label' => 'Natuur, park of tuin',
                    'domain' => 'eventtype',
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
                '/terms',
                'At most 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_terms_can_not_be_resolved_to_a_place(): void
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
                    'id' => '0.7.0.0.0',
                    'label' => 'Begeleide rondleiding',
                    'domain' => 'eventtype',
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
                'The term 0.7.0.0.0 does not exist or is not supported'
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

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_address_has_no_entries(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [],
        ];

        $expectedErrors = [
            new SchemaError(
                '/address',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_an_address_translation_is_missing_fields(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/address/nl',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_on_empty_address_fields(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => '',
                    'postalCode' => '   ',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/address/nl/postalCode',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/address/nl/streetAddress',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_address_is_missing_main_language(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => 'Yf4aZBfsUEu2NsQqsprngw',
                    'domain' => 'eventtype',
                    'label' => 'Cultuur- of ontmoetingscentrum',
                ],
            ],
            'address' => [
                'en' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/address',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_not_an_array(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'labels' => 'foo,bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_contains_something_different_than_a_string(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'labels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/0',
                'The data (object) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_not_an_array(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'hiddenLabels' => 'foo,bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/hiddenLabels',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_have_invalid_values(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'labels' => [
                'foo',
                '1',
                '',
                '   ',
                str_repeat('0123456789', 30),
            ],
            'hiddenLabels' => [
                'bar',
                '1',
                '',
                '   ',
                str_repeat('0123456789', 30),
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/1',
                'Minimum string length is 2, found 1'
            ),
            new SchemaError(
                '/labels/2',
                'Minimum string length is 2, found 0'
            ),
            new SchemaError(
                '/labels/3',
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
            new SchemaError(
                '/labels/4',
                'Maximum string length is 255, found 300'
            ),
            new SchemaError(
                '/hiddenLabels/1',
                'Minimum string length is 2, found 1'
            ),
            new SchemaError(
                '/hiddenLabels/2',
                'Minimum string length is 2, found 0'
            ),
            new SchemaError(
                '/hiddenLabels/3',
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
            new SchemaError(
                '/hiddenLabels/4',
                'Maximum string length is 255, found 300'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_contains_something_different_than_a_string(): void
    {
        $place = [
            '@id' => 'https://io.uitdatabank.be/places/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'hiddenLabels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/hiddenLabels/0',
                'The data (object) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_description_has_no_entries(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'description' => [],
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_description_is_a_string(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'description' => 'Test description',
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_description_is_missing_main_language_translation(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'description' => [
                'en' => 'This is the description',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_status_is_invalid(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'status' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/status',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_status_reason_is_empty(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'nl' => 'We zijn nog steeds gesloten.',
                    'en' => '',
                    'fr' => '   ',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/status/reason/fr',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/status/reason/en',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_status_reason_has_missing_main_language_translation(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'status' => [
                'type' => 'Unavailable',
                'reason' => [
                    'en' => 'We zijn nog steeds gesloten.',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/status/reason',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_if_booking_availability_is_invalid(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingAvailability' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingAvailability',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_if_booking_availability_has_invalid_value(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingAvailability' => [
                'type' => 'invalid value',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingAvailability/type',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_typicalAgeRange_is_not_a_string(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'typicalAgeRange' => [
                'from' => 8,
                'to' => 12,
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The data (object) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_typicalAgeRange_is_not_formatted_correctly(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'typicalAgeRange' => '8 TO 12',
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The string should match pattern: ^[\d]*-[\d]*$'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_workflowStatus_is_an_unknown_value(): void
    {
        $event = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'workflowStatus' => 'foo',
        ];

        $expectedErrors = [
            new SchemaError(
                '/workflowStatus',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_availableFrom_is_in_an_invalid_format(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'availableFrom' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableFrom',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_availableTo_is_in_an_invalid_format(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'availableTo' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableTo',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_phone(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '   ',
                    '',
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/phone/1',
                'The string should match pattern: \S'
            ),
            new SchemaError(
                '/contactPoint/phone/2',
                'Minimum string length is 1, found 0'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_phone_type(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    123,
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/phone/1',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_email(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    'publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/email/1',
                'The data must match the \'email\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_empty_email(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    '   ',
                    '',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/email/1',
                'The data must match the \'email\' format'
            ),
            new SchemaError(
                '/contactPoint/email/2',
                'The data must match the \'email\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_email_type(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    123,
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/email/1',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_url(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/url/1',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_empty_url(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    '   ',
                    '',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint/url/1',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/contactPoint/url/2',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_creates_a_place_if_contactPoint_has_missing_properties(): void
    {
        $placeId = 'c4f1515a-7a73-4e18-a53a-9bf201d6fc9b';

        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'contactPoint' => [
                'email' => ['info@publiq.be'],
            ],
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

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($place)
            ->build('POST');

        $response = $this->importPlaceRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $placeId,
                'placeId' => $placeId,
                'url' => self::PLACE_URI . $placeId,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateBookingInfo($placeId, new BookingInfo()),
                new UpdateContactPoint($placeId, new ContactPoint([], ['info@publiq.be'], [])),
                new DeleteTypicalAgeRange($placeId),
                new ImportLabels($placeId, new Labels()),
                new ImportImages($placeId, new ImageCollection()),
                new ImportVideos($placeId, new VideoCollection()),
                new DeleteCurrentOrganizer($placeId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_an_invalid_tariff(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => 'Senioren',
                    'price' => '100',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1/price',
                'The data (string) must match the type: number'
            ),
            new SchemaError(
                '/priceInfo/1/priceCurrency',
                'The data should match one item from enum'
            ),
            new SchemaError(
                '/priceInfo/1/name',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_tariff_has_no_name(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'price' => 8,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1',
                'The required properties (name) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_empty_names(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                        'fr' => '',
                        'en' => '   ',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Senioren',
                        'fr' => '',
                        'en' => '   ',
                    ],
                    'price' => 8,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1/name/fr',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/priceInfo/1/name/en',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_no_base_tariff(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Kinderen',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo',
                'At least 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_more_than_one_base_tariff(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basis',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 11,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo',
                'At most 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_no_main_language(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'en' => 'Basis',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'en' => 'Kids',
                    ],
                    'price' => 11,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/1/name',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_duplicate_names(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basis',
                    ],
                    'price' => 15,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Kinderen',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Kinderen',
                    ],
                    'price' => 5,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/2/name/nl',
                'Tariff name "Kinderen" must be unique.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_phone_numbers(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.15.0.0.0',
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
            'bookingInfo' => [
                'phone' => [
                    '044/444444',
                    '055/555555',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/phone',
                'The data (array) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_empty_phone_number(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'phone' => '   ',
                '',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/phone',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_email_addresses(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'email' => [
                    'info@publiq.be',
                    'test@publiq.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/email',
                'The data (array) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_email_address(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'email' => 'https://www.publiq.be',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/email',
                'The data must match the \'email\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_urls(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => [
                    'http://www.publiq.be',
                    'http://www.uitdatabank.be',
                ],
                'urlLabel' => [
                    'nl' => 'booking info label',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/url',
                'The data (array) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_url(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => 'info@publiq.be',
                'urlLabel' => [
                    'nl' => 'booking info label',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/url',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_urlLabel(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => 'Publiq vzw',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/urlLabel',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_empty_urlLabel(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'publiq vzw',
                    'en' => '   ',
                    'fr' => '',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/urlLabel/fr',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/bookingInfo/urlLabel/en',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_missing_urlLabel(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo',
                '\'urlLabel\' property is required by \'url\' property'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_no_main_language_translation(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'en' => 'publiq vzw',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/urlLabel',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_availabilityStarts_or_availabilityEnds(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'availabilityStarts' => '01/01/2018',
                'availabilityEnds' => '2018-01-02',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/availabilityStarts',
                'The data must match the \'date-time\' format'
            ),
            new SchemaError(
                '/bookingInfo/availabilityEnds',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_availabilityEnds_before_availabilityStarts(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'bookingInfo' => [
                'availabilityStarts' => '2028-05-17T22:00:00+00:00',
                'availabilityEnds' => '2020-05-17T22:00:00+00:00',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                'bookingInfo/availabilityEnds',
                'availabilityEnds should not be before availabilityStarts'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_is_missing_id(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'mediaObject' => [
                [
                    'foo' => 'bar',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0',
                'The required properties (@id) are missing'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_has_an_invalid_contentUrl_or_thumbnailUrl(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                    'contentUrl' => 'info@publiq.be',
                    'thumbnailUrl' => 'info@publiq.be',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/contentUrl',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/mediaObject/0/thumbnailUrl',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_has_an_empty_description_or_copyright(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    'description' => '',
                    'copyrightHolder' => '   ',
                    'inLanguage' => 'nl',
                    'contentUrl' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f.jpeg',
                    'thumbnailUrl' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f.jpeg',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/description',
                'Minimum string length is 1, found 0'
            ),
            new SchemaError(
                '/mediaObject/0/copyrightHolder',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_videos_has_an_invalid_format(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'videos' => 'wrong type',
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_videos_has_invalid_values(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'videos' => [
                [
                    'id' => 'not an id',
                    'url' => 'not a url',
                    'language' => 'unsupported',
                    'copyrightHolder' => '',
                ],
                [
                    'id' => 'c03a3e8a-0346-4d32-b2ac-4aedac49dc30',
                    'url' => 'https://vimeo.com/98765432',
                    'language' => 'nl',
                    'copyrightHolder' => '   ',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/id',
                'The data must match the \'uuid\' format'
            ),
            new SchemaError(
                '/videos/0/url',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/videos/0/language',
                'The data should match one item from enum'
            ),
            new SchemaError(
                '/videos/0/copyrightHolder',
                'Minimum string length is 2, found 0'
            ),
            new SchemaError(
                '/videos/1/copyrightHolder',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_label_is_both_in_labels_and_hiddenLabels(): void
    {
        $place = [
            '@id' => 'http://io.uitdatabank.be/place/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test place',
            ],
            'calendarType' => 'permanent',
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
            'labels' => ['foo', 'UiTPAS Mechelen'],
            'hiddenLabels' => ['uitpas mechelen'],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/1',
                'Label "UiTPAS Mechelen" cannot be both in labels and hiddenLabels properties.'
            ),
        ];

        $this->assertValidationErrors($place, $expectedErrors);
    }

    public function testHandleWithDuplicatePreventionEnabledDoubleDetected(): void
    {
        $originalPlaceUri = 'http://www.example.com/place/' . self::PLACE_ID;

        $lookupDuplicatePlace = $this->createMock(LookupDuplicatePlace::class);
        $lookupDuplicatePlace->method('getDuplicatePlaceUri')->willReturn($originalPlaceUri);

        $aggregateRepository = $this->createMock(Repository::class);
        $aggregateRepository->expects($this->never())->method('save'); // because there is a duplicate this should never save

        $handler = new ImportPlaceRequestHandler(
            $aggregateRepository,
            $this->uuidGenerator,
            $this->getPlaceDenormalizer(),
            $this->getRequestBodyParser(),
            new CallableIriGenerator(fn ($placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->commandBus,
            $this->imageCollectionFactory,
            true,
            $lookupDuplicatePlace,
            new InMemoryDocumentRepository()
        );

        $this->commandBus->record();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn(self::PLACE_ID);

        $this->imageCollectionFactory->expects($this->atMost(1))
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($this->getSimplePlace())
            ->build('POST');

        $this->assertCallableThrowsApiProblem(ApiProblem::duplicatePlaceDetected(
            'A place with this address / name combination already exists. Please use the existing place for your purposes.',
            ['duplicatePlaceUri' => $originalPlaceUri]
        ), function () use ($handler, $request): void {
            $handler->handle($request);
        });
    }

    public function testHandleWithDuplicatePreventionEnabledHappyPath(): void
    {
        $lookupDuplicatePlace = $this->createMock(LookupDuplicatePlace::class);
        $lookupDuplicatePlace->method('getDuplicatePlaceUri')->willReturn(null);

        $aggregateRepository = $this->createMock(Repository::class);
        $aggregateRepository->expects($this->once())->method('save'); // because there is a duplicate this should never save

        $handler = new ImportPlaceRequestHandler(
            $aggregateRepository,
            $this->uuidGenerator,
            $this->getPlaceDenormalizer(),
            $this->getRequestBodyParser(),
            new CallableIriGenerator(fn ($placeId) => 'https://io.uitdatabank.dev/places/' . $placeId),
            $this->commandBus,
            $this->imageCollectionFactory,
            true,
            $lookupDuplicatePlace,
            new InMemoryDocumentRepository()
        );

        $this->commandBus->record();

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn(self::PLACE_ID);

        $this->imageCollectionFactory->expects($this->atMost(1))
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($this->getSimplePlace())
            ->build('POST');

        $response = $handler->handle($request);

        $expectedResponse = new JsonResponse(
            [
                'id' => self::PLACE_ID,
                'placeId' => self::PLACE_ID,
                'url' => self::PLACE_URI . self::PLACE_ID,
                'commandId' => '00000000-0000-0000-0000-000000000000',
            ],
            StatusCodeInterface::STATUS_CREATED
        );

        $this->assertEquals($expectedResponse->getBody()->getContents(), $response->getBody()->getContents());
        $this->assertEquals($expectedResponse->getStatusCode(), $response->getStatusCode());
    }

    private function getRequestBodyParser(): CombinedRequestBodyParser
    {
        return new CombinedRequestBodyParser(
            new LegacyPlaceRequestBodyParser(),
            RemoveEmptyArraysRequestBodyParser::createForPlaces(),
            new ImportTermRequestBodyParser(new PlaceCategoryResolver()),
            new ImportPriceInfoRequestBodyParser(
                [
                    'nl' => 'Basistarief',
                    'fr' => 'Tarif de base',
                    'en' => 'Base tariff',
                    'de' => 'Basisrate',
                ]
            )
        );
    }

    private function getPlaceDenormalizer(): PlaceDenormalizer
    {
        return new PlaceDenormalizer(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            new VideoDenormalizer($this->uuidFactory)
        );
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
