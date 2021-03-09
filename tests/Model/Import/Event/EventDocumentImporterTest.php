<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Event\Commands\ImportImages;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Event\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Event\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Event\Commands\UpdateTheme;
use CultuurNet\UDB3\Event\Commands\UpdateTitle;
use CultuurNet\UDB3\Event\Commands\UpdateType;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID as Udb3ModelUUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\Person\Age;
use ValueObjects\Web\Url;

class EventDocumentImporterTest extends TestCase
{
    /**
     * @var Repository|MockObject
     */
    private $repository;

    /**
     * @var ImageCollectionFactory|MockObject
     */
    private $imageCollectionFactory;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var ConsumerInterface|MockObject
     */
    private $consumer;

    /**
     * @var ConsumerSpecificationInterface|MockObject
     */
    private $shouldApprove;

    /**
     * @var LockedLabelRepository|MockObject
     */
    private $lockedLabelRepository;

    /**
     * @var DocumentImporterInterface
     */
    private $importer;

    public function setUp()
    {
        $this->repository = $this->createMock(Repository::class);
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);
        $this->commandBus = new TraceableCommandBus();
        $this->consumer = $this->createMock(ConsumerInterface::class);
        $this->shouldApprove = $this->createMock(ConsumerSpecificationInterface::class);
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);

        $eventDocumentImporter = new EventDocumentImporter(
            $this->repository,
            new EventDenormalizer(),
            $this->imageCollectionFactory,
            $this->commandBus,
            $this->shouldApprove,
            $this->lockedLabelRepository,
            new NullLogger()
        );

        $placeDocumentRepository = new InMemoryDocumentRepository();
        $placeDocumentRepository->save(
            $this->getPlaceDocument()->toJsonDocument()
        );

        $this->importer = new TermPreProcessingDocumentImporter(
            $eventDocumentImporter,
            new EventLegacyBridgeCategoryResolver()
        );
    }

    /**
     * @test
     */
    public function it_should_create_an_new_event_and_publish_it_if_no_aggregate_exists_for_the_given_id()
    {
        $document = $this->getEventDocument();
        $id = $document->getId();

        $event = Event::create(
            $id,
            new Language('nl'),
            new Title('Voorbeeld naam'),
            new EventType('0.7.0.0.0', 'Begeleide rondleiding'),
            new LocationId('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0'),
            new Calendar(
                CalendarType::SINGLE(),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                [
                    new Timestamp(
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                    ),
                ],
                []
            ),
            new Theme('1.17.0.0.0', 'Antiek en brocante')
        );
        $event->publish(\DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'));

        $this->expectEventDoesNotExist($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();
        $this->expectCreateEvent($event);

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateAudience($id, new Audience(AudienceType::EVERYONE())),
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new ImportImages($id, new ImageCollection()),
            new ImportLabels($id, new Labels()),
            new DeleteCurrentOrganizer($id),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_create_an_new_event_and_approve_it_if_no_aggregate_exists_and_the_consumer_can_approve()
    {
        $document = $this->getEventDocument();
        $id = $document->getId();

        $this->shouldApprove->expects($this->once())
            ->method('satisfiedBy')
            ->with($this->consumer)
            ->willReturn(true);

        $event = Event::create(
            $id,
            new Language('nl'),
            new Title('Voorbeeld naam'),
            new EventType('0.7.0.0.0', 'Begeleide rondleiding'),
            new LocationId('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0'),
            new Calendar(
                CalendarType::SINGLE(),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                [
                    new Timestamp(
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                        \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                    ),
                ],
                []
            ),
            new Theme('1.17.0.0.0', 'Antiek en brocante')
        );
        $event->publish(\DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'));
        $event->approve();

        $this->expectEventDoesNotExist($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();
        $this->expectCreateEvent($event);

        $this->commandBus->record();

        $this->importer->import($document, $this->consumer);

        $expectedCommands = [
            new UpdateAudience($id, new Audience(AudienceType::EVERYONE())),
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new ImportImages($id, new ImageCollection()),
            new ImportLabels($id, new Labels()),
            new DeleteCurrentOrganizer($id),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_event_if_an_aggregate_exists_for_the_given_id()
    {
        $document = $this->getEventDocument();
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateTitle($id, new Language('nl'), new Title('Voorbeeld naam')),
            new UpdateType($id, new EventType('0.7.0.0.0', 'Begeleide rondleiding')),
            new UpdateLocation($id, new LocationId('f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0')),
            new UpdateCalendar(
                $id,
                new Calendar(
                    CalendarType::SINGLE(),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                    [
                        new Timestamp(
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                    ],
                    []
                )
            ),
            new UpdateTheme($id, new Theme('1.17.0.0.0', 'Antiek en brocante')),
            new UpdateAudience($id, new Audience(AudienceType::EVERYONE())),
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new ImportImages($id, new ImageCollection()),
            new ImportLabels($id, new Labels()),
            new DeleteCurrentOrganizer($id),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_the_description_and_translations()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['description'] = [
            'nl' => 'Voorbeeld beschrijving',
            'en' => 'Example description',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateDescription($id, new Language('nl'), new Description('Voorbeeld beschrijving')),
            $recordedCommands
        );

        $this->assertContainsObject(
            new UpdateDescription($id, new Language('en'), new Description('Example description')),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_organizer_id()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['organizer'] = [
            '@id' => 'http://io.uitdatabank.be/organizers/a106a4cb-5c5f-496b-97e0-4d63b9e09260',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateOrganizer($id, 'a106a4cb-5c5f-496b-97e0-4d63b9e09260'),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_typical_age_range()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['typicalAgeRange'] = '8-12';
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateTypicalAgeRange($id, new AgeRange(new Age(8), new Age(12))),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_price_info()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['priceInfo'] = [
            [
                'category' => 'base',
                'name' => ['nl' => 'Basistarief'],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdatePriceInfo(
                $id,
                new PriceInfo(
                    new BasePrice(
                        new Price(1000),
                        Currency::fromNative('EUR')
                    )
                )
            ),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_import_media_object_references()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['mediaObject'] = [
            [
                '@id' => 'https://io.uitdatabank.be/images/6984df33-62b4-4c94-ba2d-59d4a87d17dd.png',
                'description' => 'Example description',
                'copyrightHolder' => 'Bob',
                'inLanguage' => 'en',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/ff29632f-c277-4e27-bb97-3fdb14e90279.png',
                'description' => 'Voorbeeld beschrijving',
                'copyrightHolder' => 'Bob',
                'inLanguage' => 'nl',
            ],
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoLockedLabels();
        $this->expectNoUnlockedLabels();

        $expectedImages = ImageCollection::fromArray(
            [
                new Image(
                    new UUID('6984df33-62b4-4c94-ba2d-59d4a87d17dd'),
                    MIMEType::fromSubtype('png'),
                    new ImageDescription('Example description'),
                    new CopyrightHolder('Bob'),
                    Url::fromNative('https://io.uitdatabank.be/images/6984df33-62b4-4c94-ba2d-59d4a87d17dd.png'),
                    new Language('en')
                ),
                new Image(
                    new UUID('ff29632f-c277-4e27-bb97-3fdb14e90279'),
                    MIMEType::fromSubtype('png'),
                    new ImageDescription('Voorbeeld beschrijving'),
                    new CopyrightHolder('Bob'),
                    Url::fromNative('https://io.uitdatabank.be/images/ff29632f-c277-4e27-bb97-3fdb14e90279.png'),
                    new Language('nl')
                ),
            ]
        );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->with(
                new MediaObjectReferences(
                    MediaObjectReference::createWithMediaObjectId(
                        new Udb3ModelUUID('6984df33-62b4-4c94-ba2d-59d4a87d17dd'),
                        new Udb3ModelDescription('Example description'),
                        new CopyrightHolder('Bob'),
                        new Udb3ModelLanguage('en')
                    ),
                    MediaObjectReference::createWithMediaObjectId(
                        new Udb3ModelUUID('ff29632f-c277-4e27-bb97-3fdb14e90279'),
                        new Udb3ModelDescription('Voorbeeld beschrijving'),
                        new CopyrightHolder('Bob'),
                        new Udb3ModelLanguage('nl')
                    )
                )
            )
            ->willReturn($expectedImages);

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new ImportImages($id, $expectedImages),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_event_with_labels()
    {
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['labels'] = [
            'foo',
            'bar',
        ];
        $body['hiddenLabels'] = [
            'lorem',
            'ipsum',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();

        $lockedLabels = new Labels(
            new Label(new LabelName('locked1')),
            new Label(new LabelName('locked2'))
        );
        $unlockedLabels = new Labels(
            new Label(new LabelName('foo'), true),
            new Label(new LabelName('bar'), true),
            new Label(new LabelName('lorem'), false),
            new Label(new LabelName('ipsum'), false)
        );
        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($id)
            ->willReturn($lockedLabels);

        $this->lockedLabelRepository->expects($this->once())
            ->method('getUnlockedLabelsForItem')
            ->with($id)
            ->willReturn($unlockedLabels);

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            (
                new ImportLabels(
                    $this->getEventId(),
                    new Labels(
                        new Label(new LabelName('foo'), true),
                        new Label(new LabelName('bar'), true),
                        new Label(new LabelName('lorem'), false),
                        new Label(new LabelName('ipsum'), false)
                    )
                )
            )->withLabelsToKeepIfAlreadyOnOffer($lockedLabels)
                ->withLabelsToRemoveWhenOnOffer($unlockedLabels),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_force_audience_type_to_education_for_dummy_place(): void
    {
        $dummyPlaceId = UUID::generateAsString();
        LocationId::setDummyPlaceForEducationIds([$dummyPlaceId]);
        $document = $this->getEventDocument();
        $body = $document->getBody();
        $body['audience']['audienceType'] = AudienceType::EVERYONE()->toNative();
        $body['location']['@id'] = 'https://io.uitdatabank.be/places/' . $dummyPlaceId;
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectEventIdExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateAudience(
                $id,
                new Audience(AudienceType::EDUCATION())
            ),
            $recordedCommands
        );
    }

    private function getEventId()
    {
        return 'c33b4498-0932-4fbe-816f-c6641f30ba3b';
    }

    /**
     * @return array
     */
    private function getEventData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/events/c33b4498-0932-4fbe-816f-c6641f30ba3b',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
                'fr' => 'Nom example',
                'en' => 'Example name',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-01-01T12:00:00+01:00',
            'endDate' => '2018-01-01T17:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.7.0.0.0',
                ],
                [
                    'id' => '1.17.0.0.0',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/places/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            ],
            'availableFrom' => '2018-01-01T00:00:00+01:00',
        ];
    }

    /**
     * @return DecodedDocument
     */
    private function getEventDocument()
    {
        return new DecodedDocument($this->getEventId(), $this->getEventData());
    }

    /**
     * @return string
     */
    private function getPlaceId()
    {
        return 'f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0';
    }

    /**
     * @return array
     */
    private function getPlaceData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/places/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld locatienaam',
                'fr' => 'Nom example',
                'en' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
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
    }

    /**
     * @return DecodedDocument
     */
    private function getPlaceDocument()
    {
        return new DecodedDocument($this->getPlaceId(), $this->getPlaceData());
    }

    /**
     * @param string $eventId
     */
    private function expectEventIdExists($eventId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($eventId)
            ->willReturn($this->createMock(Event::class));
    }

    private function expectEventDoesNotExist($eventId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($eventId)
            ->willThrowException(new AggregateNotFoundException());
    }

    private function expectCreateEvent(Event $expectedEvent)
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Event $event) use ($expectedEvent) {
                return $expectedEvent->getAggregateRootId() === $event->getAggregateRootId();
            }));
    }

    private function expectNoImages()
    {
        $this->imageCollectionFactory->expects($this->any())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());
    }

    private function expectNoLockedLabels()
    {
        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Labels());
    }

    private function expectNoUnlockedLabels()
    {
        $this->lockedLabelRepository->expects($this->any())
            ->method('getUnlockedLabelsForItem')
            ->willReturn(new Labels());
    }

    private function assertContainsObject($needle, array $haystack)
    {
        $this->assertContains(
            $needle,
            $haystack,
            '',
            false,
            false
        );
    }
}
