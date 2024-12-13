<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Event\Commands\DeleteOnlineUrl;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateOnlineUrl;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumber;
use CultuurNet\UDB3\Model\ValueObject\Contact\TelephoneNumbers;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
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
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Model\ValueObject\Web\TranslatedWebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLabel;
use CultuurNet\UDB3\Model\ValueObject\Web\WebsiteLink;
use CultuurNet\UDB3\Offer\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\UpdateType;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ImportEventRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private MockObject $aggregateRepository;

    private MockObject $uuidGenerator;

    private TraceableCommandBus $commandBus;

    private MockObject $imageCollectionFactory;

    private ImportEventRequestHandler $importEventRequestHandler;

    protected function setUp(): void
    {
        $this->aggregateRepository = $this->createMock(Repository::class);
        $this->uuidGenerator = $this->createMock(UuidGeneratorInterface::class);
        $this->commandBus = new TraceableCommandBus();
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);

        $placeIriGenerator = new CallableIriGenerator(
            fn (string $placeId) => 'https://io.uitdatabank.dev/places/' . $placeId
        );

        $locationRepository = new InMemoryDocumentRepository();
        $locationRepository->save(new JsonDocument('5cf42d51-3a4f-46f0-a8af-1cf672be8c84', '{}'));

        $organizerRepository = new InMemoryDocumentRepository();
        $organizerRepository->save(new JsonDocument('f4ee4dac-5161-4f5f-98ce-922289b2def3', '{}'));

        $this->importEventRequestHandler = new ImportEventRequestHandler(
            $this->aggregateRepository,
            $this->uuidGenerator,
            new CallableIriGenerator(fn (string $eventId) => 'https://io.uitdatabank.dev/events/' . $eventId),
            new EventDenormalizer(),
            new CombinedRequestBodyParser(
                new LegacyEventRequestBodyParser($placeIriGenerator),
                RemoveEmptyArraysRequestBodyParser::createForEvents(),
                new ImportTermRequestBodyParser(new EventCategoryResolver()),
                new ImportPriceInfoRequestBodyParser(
                    [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tariff',
                        'de' => 'Basisrate',
                    ]
                ),
                new OnlineLocationPolyfillRequestBodyParser($placeIriGenerator)
            ),
            $this->commandBus,
            $this->imageCollectionFactory,
            $locationRepository,
            $organizerRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_without_id(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_ignores_empty_list_properties_and_null_values(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'labels' => [],
            'hiddenLabels' => [],
            'mediaObject' => [null],
            'priceInfo' => [],
            'openingHours' => [],
            'videos' => [],
            'contactPoint' => [
                'email' => [null],
                'phone' => null,
            ],
            'bookingInfo' => null,
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_returns_200_OK_instead_of_201_Created_for_new_events_if_using_old_imports_path(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withUriFromString('/imports/events')
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_with_id(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->never())
            ->method('generate');

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->willThrowException(new AggregateNotFoundException());

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_updates_an_event_with_given_id(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->never())
            ->method('generate');

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->once())
            ->method('load');

        $this->aggregateRepository->expects($this->never())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateTitle($eventId, new Language('nl'), new Title('Pannenkoeken voor het goede doel')),
                new UpdateType($eventId, '1.50.0.0.0'),
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new UpdateLocation($eventId, new LocationId('5cf42d51-3a4f-46f0-a8af-1cf672be8c84')),
                new UpdateCalendar($eventId, new PermanentCalendar(new OpeningHours())),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_without_id_but_with_all_properties(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            '@id' => 'https://io.uitdatabank.dev/events/' . $eventId,
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Nederlandse naam',
                'en' => 'English name',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '2021-05-17T22:00:00+00:00',
            'endDate' => '2021-05-17T22:00:00+00:00',
            'status' => [
                'type' => 'Available',
                'reason' => [
                    'nl' => 'Nederlandse reden',
                    'fr' => 'Raison français',
                    'de' => 'Deutscher Grund',
                    'en' => 'English reason',
                ],
            ],
            'subEvent' => [
                [
                    'id' => 0,
                    'startDate' => '2021-05-17T22:00:00+00:00',
                    'endDate' => '2021-05-17T22:00:00+00:00',
                    'status' => [
                        'type' => 'Available',
                        'reason' => [
                            'nl' => 'Nederlandse reden',
                            'fr' => 'Raison français',
                            'de' => 'Deutscher Grund',
                            'en' => 'English reason',
                        ],
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
            ],
            'availableFrom' => '2021-05-17T22:00:00+00:00',
            'availableTo' => '2021-05-17T22:00:00+00:00',
            'workflowStatus' => 'DRAFT',
            'attendanceMode' => 'mixed',
            'onlineUrl' => 'https://www.publiq.be/livestream',
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'typicalAgeRange' => '6-12',
            'description' => [
                'nl' => 'Nederlandse beschrijving',
                'en' => 'English description',
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'price' => 10.5,
                    'priceCurrency' => 'EUR',
                    'name' => [
                        'nl' => 'Basistarief',
                        'en' => 'Base tariff',
                    ],
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '016 12 34 56',
                    '0497 11 22 33',
                ],
                'email' => [
                    'info@publiq.be',
                    'contact@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.publiq.com',
                ],
            ],
            'bookingInfo' => [
                'phone' => '016 12 34 56',
                'email' => 'info@publiq.be',
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'Nederlandse label',
                    'en' => 'English label',
                ],
                'availabilityStarts' => '2021-05-17T22:00:00+00:00',
                'availabilityEnds' => '2021-05-21T22:00:00+00:00',
            ],
            'mediaObject' => [
                [
                    '@id' => 'https://io.uitdatabank.dev/images/85b04295-479c-40f5-b3dd-469dfb4387b3',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'https://io.uitdatabank.dev/images/pannenkoeken.png',
                    'thumbnailUrl' => 'https://io.uitdatabank.dev/images/pannenkoeken.png',
                    'description' => 'Een stapel pannenkoeken',
                    'copyrightHolder' => '© publiq vzw',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => 'https://io.uitdatabank.dev/images/pannenkoeken.png',
            'videos' => [
                [
                    'id' => 'b504cf44-9ab8-4641-9934-38d1cc67242c',
                    'url' => 'https://www.youtube.com/watch?v=cEItmb_a20D',
                    'embedUrl' => 'https://www.youtube.com/embed/cEItmb_a20D',
                    'language' => 'nl',
                    'copyrightHolder' => 'publiq vzw',
                ],
            ],
            'labels' => [
                'visible_label',
            ],
            'hiddenLabels' => [
                'hidden_label',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $imageCollection = (new ImageCollection())
            ->with(
                new Image(
                    new Uuid('85b04295-479c-40f5-b3dd-469dfb4387b3'),
                    MIMEType::fromSubtype('png'),
                    new MediaDescription('Een stapel pannenkoeken'),
                    new CopyrightHolder('© publiq vzw'),
                    new Url('https://io.uitdatabank.dev/images/8b3c82d5-6cfe-442e-946c-1f4452636d61.png'),
                    new Language('nl')
                )
            );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn($imageCollection);

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::mixed()),
                new UpdateOnlineUrl($eventId, new Url('https://www.publiq.be/livestream')),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo(
                    $eventId,
                    new BookingInfo(
                        new WebsiteLink(
                            new Url('https://www.publiq.be'),
                            (new TranslatedWebsiteLabel(
                                new Language('nl'),
                                new WebsiteLabel('Nederlandse label')
                            ))->withTranslation(
                                new Language('en'),
                                new WebsiteLabel('English label')
                            )
                        ),
                        new TelephoneNumber('016 12 34 56'),
                        new EmailAddress('info@publiq.be'),
                        BookingAvailability::fromTo(
                            new DateTimeImmutable('2021-05-17T22:00:00+00:00'),
                            new DateTimeImmutable('2021-05-21T22:00:00+00:00')
                        )
                    )
                ),
                new UpdateContactPoint(
                    $eventId,
                    new ContactPoint(
                        new TelephoneNumbers(
                            new TelephoneNumber('016 12 34 56'),
                            new TelephoneNumber('0497 11 22 33'),
                        ),
                        new EmailAddresses(
                            new EmailAddress('info@publiq.be'),
                            new EmailAddress('contact@publiq.be'),
                        ),
                        new Urls(
                            new Url('https://www.publiq.be'),
                            new Url('https://www.publiq.com'),
                        )
                    )
                ),
                new UpdateDescription(
                    $eventId,
                    new Language('nl'),
                    new Description('Nederlandse beschrijving')
                ),
                new UpdateTypicalAgeRange($eventId, AgeRange::fromString('6-12')),
                new UpdatePriceInfo(
                    $eventId,
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
                new UpdateTitle(
                    $eventId,
                    new Language('en'),
                    new Title('English name')
                ),
                new UpdateDescription(
                    $eventId,
                    new Language('en'),
                    new Description('English description')
                ),
                new ImportLabels(
                    $eventId,
                    new Labels(
                        new Label(new LabelName('visible_label'), true),
                        new Label(new LabelName('hidden_label'), false)
                    )
                ),
                new ImportImages($eventId, $imageCollection),
                new ImportVideos(
                    $eventId,
                    new VideoCollection(
                        (new Video(
                            'b504cf44-9ab8-4641-9934-38d1cc67242c',
                            new Url('https://www.youtube.com/watch?v=cEItmb_a20D'),
                            new Language('nl')
                        ))->withCopyrightHolder(new CopyrightHolder('publiq vzw')),
                    )
                ),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_ignores_an_empty_timeSpan(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'permanent',
                'timeSpans' => [],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_handles_location_in_string_format(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            'calendar' => [
                'calendarType' => 'permanent',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throw_if_body_is_missing(): void
    {
        $request = (new Psr7RequestBuilder())
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyMissing(),
            fn () => $this->importEventRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_location_is_not_found(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5df22882-0ce9-47ca-84a3-2cd22c79499e',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/location',
                'The location with id "5df22882-0ce9-47ca-84a3-2cd22c79499e" was not found.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_organizer_is_not_found(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'organizer' => [
                '@id' => 'https://io.uitdatabank.dev/organizers/5df22882-0ce9-47ca-84a3-2cd22c79499e',
            ],
            'calendarType' => 'permanent',
        ];

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $expectedErrors = [
            new SchemaError(
                '/organizer',
                'The organizer with id "5df22882-0ce9-47ca-84a3-2cd22c79499e" was not found.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-4701
     */
    public function it_does_not_crash_on_empty_location_object_but_returns_an_invalid_data_api_problem(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => (object) [],
        ];

        $expectedErrors = [
            new SchemaError(
                '/location',
                'The required properties (@id) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_and_ignores_address(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'address' => [
                'addressCountry' => 'BE',
                'addressLocality' => '',
                'postalCode' => '',
                'streetAddress' => '',
            ],
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'permanent',
                'openingHours' => [
                    [
                        'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        'opens' => '08:00',
                        'closes' => '17:00',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_missing_calendar(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_permanent_calendar(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '0.5.0.0.0',
            ],
            'theme' => [
                'id' => '0.52.0.0.0',
                'label' => 'Circus',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'permanent',
                'openingHours' => [
                    [
                        'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        'opens' => '08:00',
                        'closes' => '17:00',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_periodic_calendar(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '1.50.0.0.0',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'periodic',
                'startDate' => '2018-05-05T18:00:00.000Z',
                'endDate' => '2022-05-05T21:00:00.000Z',
                'openingHours' => [
                    [
                        'dayOfWeek' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                        'opens' => '08:00',
                        'closes' => '17:00',
                    ],
                ],
            ],
        ];

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_single_calendar(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '1.50.0.0.0',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'single',
                'startDate' => '2018-05-05T18:00:00.000Z',
                'endDate' => '2022-05-05T21:00:00.000Z',
                'timeSpans' => [
                    [
                        'start' => '2018-05-05T18:00:00.000Z',
                        'end' => '2022-05-05T21:00:00.000Z',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_multiple_calendar(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '1.50.0.0.0',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'multiple',
                'startDate' => '2018-05-05T18:00:00.000Z',
                'endDate' => '2022-05-05T21:00:00.000Z',
                'timeSpans' => [
                    [
                        'start' => '2018-05-05T18:00:00.000Z',
                        'end' => '2020-05-05T21:00:00.000Z',
                    ],
                    [
                        'start' => '2020-05-05T18:00:00.000Z',
                        'end' => '2022-05-05T21:00:00.000Z',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_single_calendar_and_no_start_or_end_date(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '1.50.0.0.0',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'single',
                'timeSpans' => [
                    [
                        'start' => '2018-05-05T18:00:00.000Z',
                        'end' => '2022-05-05T21:00:00.000Z',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_legacy_format_with_multiple_calendar_and_no_start_or_end_date(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => 'Pannenkoeken voor het goede doel',
            'type' => [
                'id' => '1.50.0.0.0',
            ],
            'location' => [
                'id' => '5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendar' => [
                'calendarType' => 'multiple',
                'timeSpans' => [
                    [
                        'start' => '2018-05-05T18:00:00.000Z',
                        'end' => '2020-05-05T21:00:00.000Z',
                    ],
                    [
                        'start' => '2020-05-05T18:00:00.000Z',
                        'end' => '2022-05-05T21:00:00.000Z',
                    ],
                ],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_new_event_from_single_calendar_with_missing_subEvent(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_a_required_property_is_missing(): void
    {
        $event = [
            'foo' => 'bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (mainLanguage, name, terms, location) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_main_language_has_wrong_value(): void
    {
        $event = [
            'mainLanguage' => 'foo',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/mainLanguage',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_main_language_has_wrong_type(): void
    {
        $event = [
            'mainLanguage' => [
                'nl',
            ],
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/mainLanguage',
                'The data (array) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_has_no_entries(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_entry_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
                'fr' => '   ',
                'en' => '',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => 123,
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'The data (integer) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_name_has_missing_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'en' => 'All you can eat pancakes',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/name',
                'A value in the mainLanguage (nl) is required.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_ignores_invalid_languages_inside_name(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
                'es' => 'Invalid language',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->once())
            ->method('load')
            ->willThrowException(new AggregateNotFoundException());

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new UpdateTitle($eventId, new Language('es'), new Title('Invalid language')),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'unknownType',
        ];

        $expectedErrors = [
            new SchemaError(
                '/calendarType',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_periodic_and_dates_are_missing(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (startDate, endDate) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_periodic_and_dates_are_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/startDate',
                'The data must match the \'date-time\' format'
            ),
            new SchemaError(
                '/endDate',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_periodic_and_endDate_before_startDate(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-03-05T13:44:09+01:00',
            'endDate' => '2018-02-28T13:44:09+01:00',
        ];

        $expectedErrors = [
            new SchemaError(
                '/endDate',
                'endDate should not be before startDate'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_single_and_subEvent_is_missing(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (subEvent) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_single_and_dates_are_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/subEvent/0/startDate',
                'The data must match the \'date-time\' format'
            ),
            new SchemaError(
                '/subEvent/0/endDate',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_single_and_endDate_before_startDate(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-03-05T13:44:09+01:00',
            'endDate' => '2018-02-28T13:44:09+01:00',
            'subEvent' => [
                [
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-05T13:44:09+01:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/endDate',
                'endDate should not be before startDate'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_single_and_subEvent_has_missing_fields(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    'key' => 'value',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/subEvent/0',
                'The required properties (startDate, endDate) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_single_and_subEvent_has_endDate_before_startDate(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    'startDate' => '2018-03-05T13:44:09+01:00',
                    'endDate' => '2018-02-28T13:44:09+01:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/subEvent/0/endDate',
                'endDate should not be before startDate'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_multiple_and_subEvent_is_missing(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'multiple',
        ];

        $expectedErrors = [
            new SchemaError(
                '/',
                'The required properties (subEvent) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_multiple_and_dates_are_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'multiple',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/startDate',
                'The data must match the \'date-time\' format'
            ),
            new SchemaError(
                '/endDate',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_multiple_and_endDate_before_startDate(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-03-05T13:44:09+01:00',
            'endDate' => '2018-02-28T13:44:09+01:00',
            'subEvent' => [
                [
                    'id' => 0,
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-05T13:44:09+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                    'bookingAvailability' => [
                        'type' => 'Available',
                    ],
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/endDate',
                'endDate should not be before startDate'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_multiple_and_subEvent_has_missing_fields(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    'key' => 'value',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/subEvent/0',
                'The required properties (startDate, endDate) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_calendarType_is_multiple_and_subEvent_endDate_is_before_startDate(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    'id' => 0,
                    'startDate' => '2018-03-01T13:44:09+01:00',
                    'endDate' => '2018-02-28T13:44:09+01:00',
                ],
                [
                    'id' => 0,
                    'startDate' => '2018-03-05T13:44:09+01:00',
                    'endDate' => '2018-03-04T13:44:09+01:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/subEvent/0/endDate',
                'endDate should not be before startDate'
            ),
            new SchemaError(
                '/subEvent/1/endDate',
                'endDate should not be before startDate'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_misses_required_fields(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_have_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_openingHours_are_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_dayOfWeek_is_malformed(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0/dayOfWeek',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_dayOfWeek_has_unknown_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'periodic',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/openingHours/0/dayOfWeek/2',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_polyfills_missing_nil_location_for_online_attendanceMode(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'online',
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::online()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => [
                'mode' => 'offline',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'The data (object) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => '   ',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_has_an_invalid_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'remote',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_is_empty_and_location_is_online(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/00000000-0000-0000-0000-000000000000',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'Attendance mode "offline" needs to have a real location.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_is_offline_and_location_is_online(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/00000000-0000-0000-0000-000000000000',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'offline',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'Attendance mode "offline" needs to have a real location.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_is_online_and_location_is_real(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'online',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'Attendance mode "online" needs to have an online location.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_attendanceMode_is_mixed_and_location_is_online(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/00000000-0000-0000-0000-000000000000',
            ],
            'calendarType' => 'permanent',
            'attendanceMode' => 'mixed',
        ];

        $expectedErrors = [
            new SchemaError(
                '/attendanceMode',
                'Attendance mode "mixed" needs to have a real location.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_onlineUrl_is_provided_on_offline_event(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'onlineUrl' => 'https://www.publiq.be/livestream',
        ];

        $expectedErrors = [
            new SchemaError(
                '/onlineUrl',
                'An onlineUrl can not be used in combination with an offline attendanceMode.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_onlineUrl_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'onlineUrl' => 'rtp://www.publiq.be/livestream',
        ];

        $expectedErrors = [
            new SchemaError(
                '/onlineUrl',
                'The string should match pattern: ^http[s]?:\/\/\w'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_onlineUrl_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'onlineUrl' => '   ',
        ];

        $expectedErrors = [
            new SchemaError(
                '/onlineUrl',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'Array should have at least 1 items, 0 found'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_is_missing_an_id(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'label' => 'foo',
                    'domain' => 'eventtype',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0',
                'The required properties (id) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_id_is_not_a_string(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => 1,
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     * @bugfix https://jira.uitdatabank.be/browse/III-4705
     */
    public function it_throws_if_terms_id_is_not_known_and_no_domain_is_set(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.51.12.0.',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The term 1.51.12.0. does not exist or is not supported'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_id_is_not_known(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1',
                    'label' => 'foo',
                    'domain' => 'facilities',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The term 1 does not exist or is not supported'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_has_more_then_one_event_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'domain' => 'eventtype',
                ],
                [
                    'id' => '0.5.0.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms',
                'At most 1 array items must match schema'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_terms_can_not_be_resolved_to_an_event(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
                    'domain' => 'eventtype',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
        ];

        $expectedErrors = [
            new SchemaError(
                '/terms/0/id',
                'The term 0.14.0.0.0 does not exist or is not supported'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_audienceType_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'label' => 'Eten en drinken',
                    'domain' => 'eventtype',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'audience' => 'everyone',
        ];

        $expectedErrors = [
            new SchemaError(
                '/audience',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_audienceType_has_unknown_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                    'label' => 'Eten en drinken',
                    'domain' => 'eventtype',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'audience' => [
                'audienceType' => 'foo',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/audience/audienceType',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_labels_and_hiddenLabels_have_wrong_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'labels' => 'foo,bar',
            'hiddenLabels' => 'foo,bar',
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels',
                'The data (string) must match the type: array'
            ),
            new SchemaError(
                '/hiddenLabels',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_labels_and_hiddenLabels_have_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'labels' => [
                1,
                true,
                '',
                '   ',
                ' d',
                str_repeat('abcde', 51) . 'f',
                'a;a',
            ],
            'hiddenLabels' => [
                1,
                true,
                '',
                '   ',
                ' d',
                str_repeat('abcde', 51) . 'f',
                'a;a',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/0',
                'The data (integer) must match the type: string'
            ),
            new SchemaError(
                '/labels/1',
                'The data (boolean) must match the type: string'
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
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
            new SchemaError(
                '/labels/5',
                'Maximum string length is 255, found 256'
            ),
            new SchemaError(
                '/labels/6',
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
            new SchemaError(
                '/hiddenLabels/0',
                'The data (integer) must match the type: string'
            ),
            new SchemaError(
                '/hiddenLabels/1',
                'The data (boolean) must match the type: string'
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
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
            new SchemaError(
                '/hiddenLabels/5',
                'Maximum string length is 255, found 256'
            ),
            new SchemaError(
                '/hiddenLabels/6',
                'The string should match pattern: ^(?=.{2,255}$)(?=.*\S.*\S.*)[^;]*$'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_has_no_entries(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'description' => [],
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (array) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_is_a_string(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'description' => 'Test description',
        ];

        $expectedErrors = [
            new SchemaError(
                '/description',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_description_is_missing_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'status' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/status',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_reason_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_status_has_no_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingAvailability_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingAvailability' => 'should not be a string',
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingAvailability',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingAvailability_has_invalid_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_organizer_id_is_invalid(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'organizer' => [
                '@id' => 'https://io.uitdatabank.dev/e78befcb-d337-4646-a721-407f69f0ce22',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/organizer/%40id',
                'The string should match pattern: ^http[s]?:\/\/.+?\/organizer[s]?\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\/]?'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_typicalAgeRange_has_wrong_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'typicalAgeRange' => 12,
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_typicalAgeRange_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'typicalAgeRange' => '8 TO 12',
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                'The string should match pattern: ^[\d]*-[\d]*$'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_typicalAgeRange_to_value_is_smaller(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'typicalAgeRange' => '12-8',
        ];

        $expectedErrors = [
            new SchemaError(
                '/typicalAgeRange',
                '"From" age should not be greater than the "to" age.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_workflowStatus_has_unknown_value(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'workflowStatus' => 'unknown value',
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
    public function it_imports_a_new_event_and_deletes_it_if_workflowStatus_deleted(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'workflowStatus' => 'DELETED',
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteOffer($eventId),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_availableFrom_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'availableFrom' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableFrom',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_availableTo_has_invalid_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'availableTo' => '05/03/2018',
        ];

        $expectedErrors = [
            new SchemaError(
                '/availableTo',
                'The data must match the \'date-time\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'contactPoint' => '02 551 18 70',
        ];

        $expectedErrors = [
            new SchemaError(
                '/contactPoint',
                'The data (string) must match the type: object'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_phone(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '   ',
                    '',
                    123,
                ],
                'email' => [],
                'url' => [],
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
            new SchemaError(
                '/contactPoint/phone/3',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_email(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [],
                'email' => [
                    'info@publiq.be',
                    '   ',
                    '',
                    'publiq.be',
                    123,
                ],
                'url' => [],
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
            new SchemaError(
                '/contactPoint/email/3',
                'The data must match the \'email\' format'
            ),
            new SchemaError(
                '/contactPoint/email/4',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_contactPoint_has_invalid_url(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'phone' => [],
                'email' => [],
                'url' => [
                    'https://www.publiq.be',
                    '   ',
                    '',
                    'www.uitdatabank.be',
                    123,
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
            new SchemaError(
                '/contactPoint/url/3',
                'The data must match the \'uri\' format'
            ),
            new SchemaError(
                '/contactPoint/url/4',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_creates_event_with_contactPoint_that_has_missing_properties(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'contactPoint' => [
                'email' => ['info@publiq.be'],
            ],
        ];

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($event)
            ->build('PUT');

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint(
                    $eventId,
                    new ContactPoint(
                        new TelephoneNumbers(),
                        new EmailAddresses(new EmailAddress('info@publiq.be')),
                        new Urls()
                    )
                ),
                new DeleteTypicalAgeRange($eventId),
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_invalid_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_tariff_has_no_name(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_empty_name(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_no_base_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_more_than_one_base_tariff(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_no_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_priceInfo_has_duplicate_names(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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
                [
                    'category' => 'tariff',
                    'name' => [
                        'en' => 'Children',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'en' => 'Students',
                    ],
                    'price' => 5,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'en' => 'Children',
                    ],
                    'price' => 5,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Kinderen',
                    ],
                    'price' => 1,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/priceInfo/2/name/nl',
                'Tariff name "Kinderen" must be unique.'
            ),
            new SchemaError(
                '/priceInfo/5/name/en',
                'Tariff name "Children" must be unique.'
            ),
            new SchemaError(
                '/priceInfo/6/name/nl',
                'Tariff name "Kinderen" must be unique.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }


    /**
     * @test
     */
    public function it_overrides_base_tariff_names(): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $given = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'priceInfo' => [
                [
                    'category' => 'base',
                    'price' => 10.5,
                    'priceCurrency' => 'EUR',
                    'name' => [
                        'de' => 'Something German',
                    ],
                ],
            ],
        ];

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());

        $this->aggregateRepository->expects($this->never())
            ->method('load');

        $this->aggregateRepository->expects($this->once())
            ->method('save');

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($given)
            ->build('PUT');

        $response = $this->importEventRequestHandler->handle($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            Json::encode([
                'id' => $eventId,
                'eventId' => $eventId,
                'url' => 'https://io.uitdatabank.dev/events/' . $eventId,
                'commandId' => Uuid::NIL,
            ]),
            $response->getBody()->getContents()
        );

        $this->assertEquals(
            [
                new UpdateAttendanceMode($eventId, AttendanceMode::offline()),
                new DeleteOnlineUrl($eventId),
                new UpdateAudience($eventId, AudienceType::everyone()),
                new UpdateBookingInfo($eventId, new BookingInfo()),
                new UpdateContactPoint($eventId, new ContactPoint()),
                new DeleteTypicalAgeRange($eventId),
                new UpdatePriceInfo(
                    $eventId,
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
                new ImportLabels($eventId, new Labels()),
                new ImportImages($eventId, new ImageCollection()),
                new ImportVideos($eventId, new VideoCollection()),
                new DeleteCurrentOrganizer($eventId),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_wrong_phone_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'phone' => 123,
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/phone',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_empty_phone(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'phone' => '   ',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/phone',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_wrong_email_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'email' => 123,
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/email',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_empty_email(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'email' => '   ',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/email',
                'The data must match the \'email\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_invalid_email(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'email' => '@publiq.be',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/email',
                'The data must match the \'email\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_wrong_url_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'url' => 123,
                'urlLabel' => [
                    'nl' => 'booking info label',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/bookingInfo/url',
                'The data (integer) must match the type: string'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_empty_url(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'url' => '   ',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_invalid_url(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'url' => 'www.publiq.be',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_empty_urlLabel(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_missing_urlLabel(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_invalid_urlLabel(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_no_urlLabel_in_main_language(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_has_invalid_availabilityStarts_or_availabilityEnds(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
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

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_bookingInfo_availabilityEnds_is_before_availabilityStarts(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'bookingInfo' => [
                'availabilityStarts' => '2005-12-31T01:02:03+00:00',
                'availabilityEnds' => '2005-12-30T01:02:03+00:00',
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                'bookingInfo/availabilityEnds',
                'availabilityEnds should not be before availabilityStarts'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_is_has_wrong_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => 'wrong type',
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_is_missing_id(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    'key' => 'value',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0',
                'The required properties (@id) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_has_wrong_url_format(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                    'contentUrl' => 'www.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f.jpeg',
                    'thumbnailUrl' => 'ftp://www.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f.jpeg',
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
                'The string should match pattern: ^http[s]?:\/\/'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_description_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => '   ',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/description',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_copyrightHolder_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => '   ',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/copyrightHolder',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_copyrightHolder_is_too_short(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => '1',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/copyrightHolder',
                'Minimum string length is 2, found 1'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_copyrightHolder_is_too_long(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => str_repeat('abcde', 50) . 'f',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/copyrightHolder',
                'Maximum string length is 250, found 251'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_language_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => '   ',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/inLanguage',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_mediaObject_language_is_unknown(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'es',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/mediaObject/0/inLanguage',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_image_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => '   ',
        ];

        $expectedErrors = [
            new SchemaError(
                '/image',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_image_is_invalid_url(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => 'io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f.png',
        ];

        $expectedErrors = [
            new SchemaError(
                '/image',
                'The data must match the \'uri\' format'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_image_has_wrong_protocol(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => 'ftp://io.uitdatabank.dev/images/5cdacc0b-a96b-4613-81e0-1748c179432f.png',
        ];

        $expectedErrors = [
            new SchemaError(
                '/image',
                'The string should match pattern: ^http[s]?:\/\/'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_has_wrong_type(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => 'wrong',
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos',
                'The data (string) must match the type: array'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_have_missing_properties(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'key' => 'value',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0',
                'The required properties (url, language) are missing'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_url_is_invalid(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'url' => 'https://www.youtube.com/123',
                    'language' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/url',
                'The string should match pattern: ^http(s?):\/\/(www\.)?((youtube\.com\/watch\?v=([^\/#&?]*))|(vimeo\.com\/([^\/#&?]*))|(youtu\.be\/([^\/#&?]*))|(youtube.com/embed/([^\/#&?]*))|(youtube.com/shorts/([^\/#&?]*)))'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_language_is_not_supported(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'es',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/language',
                'The data should match one item from enum'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_copyrightHolder_is_empty(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                    'copyrightHolder' => '   ',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/copyrightHolder',
                'The string should match pattern: \S'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_copyrightHolder_is_too_short(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                    'copyrightHolder' => 'a',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/copyrightHolder',
                'Minimum string length is 2, found 1'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_videos_copyrightHolder_is_too_long(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'videos' => [
                [
                    'url' => 'https://www.youtube.com/watch?v=123',
                    'language' => 'nl',
                    'copyrightHolder' => str_repeat('abdce', 50) . 'f',
                ],
            ],
        ];

        $expectedErrors = [
            new SchemaError(
                '/videos/0/copyrightHolder',
                'Maximum string length is 250, found 251'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_throws_if_a_label_is_both_in_labels_and_hiddenLabels(): void
    {
        $event = [
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Pannenkoeken voor het goede doel',
            ],
            'terms' => [
                [
                    'id' => '1.50.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.dev/places/5cf42d51-3a4f-46f0-a8af-1cf672be8c84',
            ],
            'calendarType' => 'permanent',
            'labels' => ['foo', 'uitpas mechelen'],
            'hiddenLabels' => ['UiTPAS Mechelen'],
        ];

        $expectedErrors = [
            new SchemaError(
                '/labels/1',
                'Label "uitpas mechelen" cannot be both in labels and hiddenLabels properties.'
            ),
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    private function assertValidationErrors(array $event, array $expectedErrors): void
    {
        $eventId = 'f2850154-553a-4553-8d37-b32dd14546e4';

        $this->uuidGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($eventId);

        $request = (new Psr7RequestBuilder())
            ->withJsonBodyFromArray($event)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedErrors),
            fn () => $this->importEventRequestHandler->handle($request)
        );
    }
}
