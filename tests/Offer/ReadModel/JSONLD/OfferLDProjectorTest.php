<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Completeness\CompletenessFromWeights;
use CultuurNet\UDB3\Completeness\Weights;
use CultuurNet\UDB3\Event\Events\Concluded;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\VideoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\Item\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionDeleted;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\VideoAdded;
use CultuurNet\UDB3\Offer\Item\Events\VideoDeleted;
use CultuurNet\UDB3\Offer\Item\Events\VideoUpdated;
use CultuurNet\UDB3\Offer\Item\ReadModel\JSONLD\ItemLDProjector;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentNullEnricher;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

class OfferLDProjectorTest extends TestCase
{
    protected InMemoryDocumentRepository $documentRepository;

    protected ItemLDProjector $projector;

    /**
     * @var DocumentRepository|MockObject
     */
    protected $organizerRepository;

    protected MediaObjectSerializer $serializer;

    protected RecordedOn $recordedOn;

    public function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerRepository = $this->createMock(DocumentRepository::class);

        $iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->serializer = new MediaObjectSerializer($iriGenerator);

        $this->projector = new ItemLDProjector(
            $this->documentRepository,
            $iriGenerator,
            new CallableIriGenerator(fn ($id) => 'http://example.com/organizers/' . $id),
            $this->organizerRepository,
            $this->serializer,
            new JsonDocumentNullEnricher(),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ],
            new VideoNormalizer(
                [
                    'nl' => 'Copyright afgehandeld door %s',
                    'fr' => 'Droits d\'auteur gérés par %s',
                    'de' => 'Urheberrecht gehandhabt von %s',
                    'en' => 'Copyright handled by %s',
                ]
            ),
            new CompletenessFromWeights(
                Weights::fromConfig([
                    'type' => 12,
                    'theme' => 5,
                    'calendarType' => 12,
                    'address' => 12,
                    'name' => 12,
                    'typicalAgeRange' => 12,
                    'mediaObject' => 8,
                    'description' => 9,
                    'priceInfo' => 7,
                    'contactPoint' => 3,
                    'bookingInfo' => 3,
                    'organizer' => 3,
                    'videos' => 2,
                ])
            )
        );

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-01T08:30:00+0100')
        );
    }

    protected function project(
        object $event,
        string $entityId,
        Metadata $metadata = null,
        DateTime $dateTime = null
    ): stdClass {
        if (null === $metadata) {
            $metadata = new Metadata();
        }

        if (null === $dateTime) {
            $dateTime = DateTime::now();
        }

        $this->projector->handle(
            new DomainMessage(
                $entityId,
                1,
                $metadata,
                $event,
                $dateTime
            )
        );

        return $this->getBody($entityId);
    }

    protected function getBody(string $id): stdClass
    {
        $document = $this->documentRepository->fetch($id);
        return $document->getBody();
    }

    /**
     * @test
     */
    public function it_retries_on_playhead_mismatch(): void
    {
        $documentRepository = $this->createMock(DocumentRepository::class);

        $projector = new ItemLDProjector(
            $documentRepository,
            new CallableIriGenerator(fn ($id) => 'http://example.com/entity/' . $id),
            new CallableIriGenerator(fn ($id) => 'http://example.com/organizers/' . $id),
            $this->createMock(DocumentRepository::class),
            $this->createMock(MediaObjectSerializer::class),
            new JsonDocumentNullEnricher(),
            [],
            new VideoNormalizer([]),
            new CompletenessFromWeights(Weights::fromConfig([]))
        );

        $documentRepository->expects($this->exactly(4))
            ->method('fetch')
            ->willReturn(
                new JsonDocument(
                    'foo',
                    Json::encode([
                        'labels' => ['label A'],
                        'playhead' => 1,
                    ])
                )
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(3))
            ->method('warning');
        $logger->expects($this->once())
            ->method('error');
        $projector->setLogger($logger);

        $projector->handle(
            new DomainMessage(
                'foo',
                3,
                new Metadata(),
                new LabelAdded('foo', 'label B'),
                DateTime::now()
            )
        );
    }

    /**
     * @test
     */
    public function it_does_not_retry_on_playhead_match(): void
    {
        $documentRepository = $this->createMock(DocumentRepository::class);

        $projector = new ItemLDProjector(
            $documentRepository,
            new CallableIriGenerator(fn ($id) => 'http://example.com/entity/' . $id),
            new CallableIriGenerator(fn ($id) => 'http://example.com/organizers/' . $id),
            $this->createMock(DocumentRepository::class),
            $this->createMock(MediaObjectSerializer::class),
            new JsonDocumentNullEnricher(),
            [],
            new VideoNormalizer([]),
            new CompletenessFromWeights(Weights::fromConfig([]))
        );

        $documentRepository->expects($this->once())
            ->method('fetch')
            ->willReturn(
                new JsonDocument(
                    'foo',
                    Json::encode([
                        'labels' => ['label A'],
                        'playhead' => 1,
                    ])
                )
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('warning');
        $logger->expects($this->never())
            ->method('error');
        $projector->setLogger($logger);

        $projector->handle(
            new DomainMessage(
                'foo',
                2,
                new Metadata(),
                new LabelAdded('foo', 'label B'),
                DateTime::now()
            )
        );
    }

    /**
     * @test
     */
    public function it_stops_retry_on_playhead_match(): void
    {
        $documentRepository = $this->createMock(DocumentRepository::class);

        $projector = new ItemLDProjector(
            $documentRepository,
            new CallableIriGenerator(fn ($id) => 'http://example.com/entity/' . $id),
            new CallableIriGenerator(fn ($id) => 'http://example.com/organizers/' . $id),
            $this->createMock(DocumentRepository::class),
            $this->createMock(MediaObjectSerializer::class),
            new JsonDocumentNullEnricher(),
            [],
            new VideoNormalizer([]),
            new CompletenessFromWeights(Weights::fromConfig([]))
        );

        $documentRepository->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                new JsonDocument(
                    'foo',
                    Json::encode([
                        'labels' => ['label A'],
                        'playhead' => 1,
                    ])
                ),
                new JsonDocument(
                    'foo',
                    Json::encode([
                        'labels' => ['label A', 'label B'],
                        'playhead' => 2,
                    ])
                )
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning');
        $logger->expects($this->never())
            ->method('error');
        $projector->setLogger($logger);

        $projector->handle(
            new DomainMessage(
                'foo',
                3,
                new Metadata(),
                new LabelAdded('foo', 'label B'),
                DateTime::now()
            )
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label(): void
    {
        $labelAdded = new LabelAdded(
            'foo',
            'label B'
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($labelAdded, 'foo');

        $this->assertEquals(
            ['label A', 'label B'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_an_invisible_label(): void
    {
        $labelAdded = new LabelAdded(
            'foo',
            'label B',
            false
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object) [
                'labels' => ['label A'],
                'hiddenLabels' => ['label B'],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 0,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_removal_of_a_label(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A', 'label B', 'label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            'label B'
        );

        $body = $this->project($labelRemoved, 'foo');

        $this->assertEquals(
            ['label A', 'label C'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_removal_of_a_hidden_label(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A', 'label B'],
                'hiddenLabels' => ['label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            'label C',
            false
        );

        $body = $this->project($labelRemoved, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object) [
                'labels' => ['label A', 'label B'],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 0,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label_to_an_event_without_existing_labels(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'bar' => 'stool',
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelAdded = new LabelAdded(
            'foo',
            'label B'
        );

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = new stdClass();
        $expectedBody->bar = 'stool';
        $expectedBody->labels = ['label B'];
        $expectedBody->modified = $this->recordedOn->toString();
        $expectedBody->playhead = 1;
        $expectedBody->completeness = 0;

        $this->assertEquals(
            $expectedBody,
            $body
        );
    }

    /**
     * @test
     */
    public function it_updates_playhead_on_labels_imported(): void
    {
        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'labels' => ['label A'],
                'hiddenLabels' => ['label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelsImported = new LabelsImported(
            'foo',
            ['label B'],
            ['label D']
        );

        $body = $this->project($labelsImported, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object) [
                'labels' => ['label A'],
                'hiddenLabels' => ['label C'],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 0,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_main_language_name_property_when_a_title_updated_event_occurs(): void
    {
        $titleUpdatedEvent = new TitleUpdated(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            'A cycling adventure'
        );

        $initialDocument = new JsonDocument(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            Json::encode([
                'mainLanguage' => 'en',
                'name' => [
                    'nl'=> 'Fietsen langs kapelletjes',
                    'en'=> 'Cycling through Flanders',
                ],
            ])
        );

        $expectedDocument = new JsonDocument(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            Json::encode([
                'mainLanguage' => 'en',
                'name' => [
                    'nl'=> 'Fietsen langs kapelletjes',
                    'en'=> 'A cycling adventure',
                ],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 12,
            ])
        );

        $this->documentRepository->save($initialDocument);

        $projectedBody = $this->project(
            $titleUpdatedEvent,
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $this->assertEquals($expectedDocument->getBody(), $projectedBody);
    }

    /**
     * @test
     */
    public function it_projects_the_translation_of_the_title(): void
    {
        $titleTranslated = new TitleTranslated(
            'foo',
            new LegacyLanguage('en'),
            'English title'
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'name' => [
                    'nl'=> 'Titel',
                ],
                'description' => [
                    'nl' => 'Omschrijving',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($titleTranslated, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object)[
                'name' => (object)[
                    'nl'=> 'Titel',
                    'en' => 'English title',
                ],
                'description' => (object)[
                    'nl' => 'Omschrijving',
                ],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 21,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_translation_of_the_description(): void
    {
        $descriptionTranslated = new DescriptionTranslated(
            'foo',
            new LegacyLanguage('en'),
            new \CultuurNet\UDB3\Description('English description')
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'name' => [
                    'nl'=> 'Titel',
                ],
                'description' => [
                    'nl' => 'Omschrijving',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($descriptionTranslated, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object)[
                'name' => (object)[
                    'nl'=> 'Titel',
                ],
                'description' => (object)[
                    'nl' => 'Omschrijving',
                    'en' => 'English description',
                ],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 21,
            ],
            $body
        );
    }

    /**
     * @test
     * @group deleteDescriptionOffer
     */
    public function it_projects_the_removal_of_the_description(): void
    {
        $descriptionDeleted = new DescriptionDeleted(
            'foo',
            new Language('nl'),
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'name' => [
                    'nl'=> 'Titel',
                ],
                'description' => [
                    'nl' => 'Omschrijving',
                    'fr' => 'Le description',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($descriptionDeleted, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object)[
                'name' => (object)[
                    'nl'=> 'Titel',
                ],
                'description' => (object)[
                    'fr' => 'Le description',
                ],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 21,
            ],
            $body
        );
    }

    /**
     * @test
     * @group DeleteDescriptionOffer
     */
    public function it_projects_the_removal_of_the_description_when_last_language(): void
    {
        $descriptionDeleted = new DescriptionDeleted(
            'foo',
            new Language('nl'),
        );

        $initialDocument = new JsonDocument(
            'foo',
            Json::encode([
                'name' => [
                    'nl'=> 'Titel',
                ],
                'description' => [
                    'nl' => 'Omschrijving',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($descriptionDeleted, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object)[
                'name' => (object)[
                    'nl'=> 'Titel',
                ],
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 12,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_updated_price_info(): void
    {
        $aggregateId = 'a5bafa9d-a71e-4624-835d-57db2832a7d8';

        $priceInfo = new PriceInfo(
            new BasePrice(
                new Money(1050, new Currency('EUR'))
            )
        );

        $priceInfo = $priceInfo->withExtraTariff(
            new Tariff(
                new MultilingualString(
                    new LegacyLanguage('nl'),
                    'Tarief inwoners'
                ),
                new Money(950, new Currency('EUR'))
            )
        );

        $priceInfo = $priceInfo->withExtraUiTPASTariff(
            new Tariff(
                new MultilingualString(
                    new LegacyLanguage('nl'),
                    'UiTPAS tarief'
                ),
                new Money(650, new Currency('EUR'))
            )
        );

        $priceInfoUpdated = new PriceInfoUpdated($aggregateId, $priceInfo);

        $initialDocument = (new JsonDocument($aggregateId))
            ->withBody(
                (object) [
                    '@id' => 'http://example.com/offer/a5bafa9d-a71e-4624-835d-57db2832a7d8',
                ]
            );

        $expectedBody = (object) [
            '@id' => 'http://example.com/offer/a5bafa9d-a71e-4624-835d-57db2832a7d8',
            'priceInfo' => [
                (object) [
                    'category' => 'base',
                    'name' => (object) [
                        'nl' => 'Basistarief',
                        'fr' => 'Tarif de base',
                        'en' => 'Base tariff',
                        'de' => 'Basisrate',
                    ],
                    'price' => 10.5,
                    'priceCurrency' => 'EUR',
                ],
                (object) [
                    'category' => 'tariff',
                    'name' => (object) ['nl' => 'Tarief inwoners'],
                    'price' => 9.50,
                    'priceCurrency' => 'EUR',
                ],
                (object) [
                    'category' => 'uitpas',
                    'name' => (object) ['nl' => 'UiTPAS tarief'],
                    'price' => 6.50,
                    'priceCurrency' => 'EUR',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 7,
        ];

        $this->documentRepository->save($initialDocument);

        $actualBody = $this->project($priceInfoUpdated, $aggregateId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $actualBody);
    }

    /**
     * @test
     */
    public function it_adds_a_media_object_when_an_image_is_added_to_the_event(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $expectedMediaObjects = [
            (object) [
                '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                '@type' => 'schema:ImageObject',
                'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'description' => 'The Gleaners',
                'copyrightHolder' => 'Jean-François Millet',
                'inLanguage' => 'en',
            ],
        ];
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageAddedEvent = new ImageAdded($eventId, $image);
        $eventBody = $this->project($imageAddedEvent, $eventId);

        $this->assertEquals(
            $expectedMediaObjects,
            $eventBody->mediaObject
        );
    }

    public function mediaObjectDataProvider(): array
    {
        $eventId = 'event-1';

        $initialJsonStructure = [
            'image' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
        ];

        $initialJsonStructureWithMedia = $initialJsonStructure + [
                'mediaObject' => [
                    (object) [
                        '@id' => 'http://example.com/entity/de305d54-ddde-eddd-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
                        'description' => 'my best pokerface',
                        'copyrightHolder' => 'Hans Langucci',
                        'inLanguage' => 'en',
                    ],
                    (object) [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ];

        $image1 = new Image(
            new UUID('de305d54-ddde-eddd-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('my best pokerface'),
            new CopyrightHolder('Hans Langucci'),
            new Url('http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );

        $image2 = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );

        $expectedWithoutLastImage = (object) [
            'image' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
            'mediaObject' => [
                (object) [
                    '@id' => 'http://example.com/entity/de305d54-ddde-eddd-adb2-eb6b9e546014',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png',
                    'description' => 'my best pokerface',
                    'copyrightHolder' => 'Hans Langucci',
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => '2018-01-01T08:30:00+01:00',
            'playhead' => 1,
            'completeness' => 8,
        ];

        $expectedWithoutFirstImage = (object) [
            'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            'mediaObject' => [
                (object) [
                    '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'description' => 'The Gleaners',
                    'copyrightHolder' => 'Jean-François Millet',
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => '2018-01-01T08:30:00+01:00',
            'playhead' => 1,
            'completeness' => 8,
        ];


        return [
            'document with 2 images, last image gets removed' => [
                new JsonDocument(
                    $eventId,
                    Json::encode((object) $initialJsonStructureWithMedia)
                ),
                $image2,
                $expectedWithoutLastImage,
            ],
            'document with 2 images, first image gets removed' => [
                new JsonDocument(
                    $eventId,
                    Json::encode((object) $initialJsonStructureWithMedia)
                ),
                $image1,
                $expectedWithoutFirstImage,
            ],
            'document without media' => [
                new JsonDocument(
                    $eventId,
                    Json::encode((object) $initialJsonStructure)
                ),
                $image1,
                (object) $initialJsonStructure,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mediaObjectDataProvider
     */
    public function it_should_remove_the_media_object_of_an_image(
        JsonDocument $initialDocument,
        Image $image,
        stdClass $expectedProjection
    ): void {
        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($initialDocument->getId(), $image);
        $eventBody = $this->project($imageRemovedEvent, $initialDocument->getId(), null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            $expectedProjection,
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_should_destroy_the_media_object_attribute_when_no_media_objects_are_left_after_removing_an_image(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertObjectNotHasProperty('mediaObject', $eventBody);
    }

    /**
     * @test
     */
    public function it_unsets_the_main_images_url_if_no_media_objects_remain(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('aa39128e-b2ca-5629-a275-b380381df0f3'),
            new MIMEType('image/jpeg'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://images.uitdatabank.be/edcea9f6-756b-4935-9c8b-d7d9c262d041.jpeg'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'mediaObject' => [
                    [
                        '@id' => 'https://io.uitdatabank.be/images/aa39128e-b2ca-5629-a275-b380381df0f3',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://images.uitdatabank.be/edcea9f6-756b-4935-9c8b-d7d9c262d041.jpeg',
                        'thumbnailUrl' => 'https://images.uitdatabank.be/edcea9f6-756b-4935-9c8b-d7d9c262d041.jpeg',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
                'image' => 'https://images.uitdatabank.be/edcea9f6-756b-4935-9c8b-d7d9c262d041.jpeg',
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertObjectNotHasProperty('mediaObject', $eventBody);
        $this->assertObjectNotHasProperty('image', $eventBody);
    }

    /**
     * @test
     */
    public function it_should_unset_a_main_image_regardless_of_protocol(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'https://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'https://example.com/entity/bef60647-0e56-4d33-87f7-110811092c4a',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://foo.bar/media/bef60647-0e56-4d33-87f7-110811092c4a.png',
                        'thumbnailUrl' => 'https://foo.bar/media/bef60647-0e56-4d33-87f7-110811092c4a.png',
                        'description' => 'Des glaneuses',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'fr',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertEquals(
            'https://foo.bar/media/bef60647-0e56-4d33-87f7-110811092c4a.png',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_should_unset_the_main_image_when_its_media_object_is_removed(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'https://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertObjectNotHasProperty('image', $eventBody);
    }

    /**
     * @test
     */
    public function it_should_make_an_image_main_when_added_to_an_item_without_existing_ones(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'pro' => 'jection',
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageAddedEvent = new ImageAdded($eventId, $image);
        $eventBody = $this->project($imageAddedEvent, $eventId);

        $this->assertEquals(
            'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertEquals(
            'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_should_make_a_new_main_image_when_deleting_an_udb2_main_image(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('7fba0270-9efa-5091-ac4a-381d6cc9394f'),
            new MIMEType('image/jpeg'),
            new Description('THE FOX'),
            new CopyrightHolder('THE FOX'),
            new Url('https://images.uitdatabank.dev/20160606/THE_FOX.jpg'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/7fba0270-9efa-5091-ac4a-381d6cc9394f',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                        'thumbnailUrl' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                        'description' => 'THE FOX',
                        'copyrightHolder' => 'THE FOX',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'https://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'https://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertEquals(
            'https://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
            $eventBody->image
        );
    }


    /**
     * @test
     */
    public function it_should_keep_the_main_image_when_deleting_an_image_with_a_lower_index(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('5ae74e68-20a3-4cb1-b255-8e405aa01ab9'),
            new MIMEType('image/png'),
            new Description('funny giphy image'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/793725b3-fc62-43b0-be67-687d55f53378',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/793725b3-fc62-43b0-be67-687d55f53378.png',
                        'thumbnailUrl' => 'http://foo.bar/media/793725b3-fc62-43b0-be67-687d55f53378.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Angelus',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertEquals(
            'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_should_keep_the_udb2_image_main_when_deleting_another_image(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('5ae74e68-20a3-4cb1-b255-8e405aa01ab9'),
            new MIMEType('image/jpeg'),
            new Description('funny giphy image'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('https://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/7fba0270-9efa-5091-ac4a-381d6cc9394f',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                        'thumbnailUrl' => 'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
                        'description' => 'THE FOX',
                        'copyrightHolder' => 'THE FOX',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'https://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'https://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'https://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertEquals(
            'https://images.uitdatabank.dev/20160606/THE_FOX.jpg',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_should_set_the_image_property_when_selecting_a_main_image(): void
    {
        $eventId = 'event-1';
        $selectedMainImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('The Gleaners'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new LegacyLanguage('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $mainImageSelecetd = new MainImageSelected($eventId, $selectedMainImage);
        $eventBody = $this->project($mainImageSelecetd, $eventId);

        $this->assertEquals(
            'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            $eventBody->image
        );
    }

    /**
     * @test
     */
    public function it_projects_video_added(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';

        $video = (new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Creative Commons'));

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoAdded = new VideoAdded($eventId, $video);
        $eventBody = $this->project($videoAdded, $eventId);

        unset($eventBody->modified);
        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                    ],
                ],
                'playhead' => 1,
                'completeness' => 14,
            ],
            $eventBody
        );
    }


    /**
     * @test
     */
    public function it_projects_multiple_videos_added(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';

        $video2 = (new Video(
            '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
            new Url('https://vimeo.com/98765432'),
            new Language('nl')
        ))->withCopyrightHolder(new CopyrightHolder('Public Domain'));

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
                'videos' => [
                    [
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                    ],
                ],

            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoAdded = new VideoAdded($eventId, $video2);
        $eventBody = $this->project($videoAdded, $eventId);

        unset($eventBody->modified);

        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                    ],
                    (object)[
                        'id' => '5c549a24-bb97-4f83-8ea5-21a6d56aff72',
                        'url' => 'https://vimeo.com/98765432',
                        'embedUrl' => 'https://player.vimeo.com/video/98765432',
                        'language' => 'nl',
                        'copyrightHolder' => 'Public Domain',
                    ],
                ],
                'playhead' => 1,
                'completeness' => 14,
            ],
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_projects_an_empty_copyright_with_a_video(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';

        $video = new Video(
            '91c75325-3830-4000-b580-5778b2de4548',
            new Url('https://www.youtube.com/watch?v=123'),
            new Language('nl')
        );

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoAdded = new VideoAdded($eventId, $video);
        $eventBody = $this->project($videoAdded, $eventId);

        unset($eventBody->modified);
        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Copyright afgehandeld door YouTube',
                    ],
                ],
                'playhead' => 1,
                'completeness' => 14,
            ],
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_projects_deleting_a_video(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';
        $videoToDeleteId = '5c549a24-bb97-4f83-8ea5-21a6d56aff72';

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'copyrightHolder' => 'Creative Commons',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                    ],
                    (object)[
                        'id' => $videoToDeleteId,
                        'url' => 'https://vimeo.com/98765432',
                        'embedUrl' => 'https://player.vimeo.com/video/98765432',
                        'copyrightHolder' => 'Public Domain',
                    ],
                    (object)[
                        'id' => 'e86a4d89-1f95-475e-b637-b986f669ecef',
                        'url' => 'https://www.youtube.com/watch?v=4335',
                        'copyrightHolder' => 'Creative Minds',
                        'embedUrl' => 'https://www.youtube.com/embed/4335',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoDeleted = new VideoDeleted($eventId, $videoToDeleteId);
        $eventBody = $this->project($videoDeleted, $eventId);

        unset($eventBody->modified);
        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'copyrightHolder' => 'Creative Commons',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                    ],
                    (object)[
                        'id' => 'e86a4d89-1f95-475e-b637-b986f669ecef',
                        'url' => 'https://www.youtube.com/watch?v=4335',
                        'copyrightHolder' => 'Creative Minds',
                        'embedUrl' => 'https://www.youtube.com/embed/4335',
                    ],
                ],
                'playhead' => 1,
                'completeness' => 14,
            ],
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_projects_deleting_a_video_and_unsets_videos(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';
        $videoId = '91c75325-3830-4000-b580-5778b2de4548';

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => $videoId,
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'copyrightHolder' => 'Creative Commons',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoDeleted = new VideoDeleted($eventId, $videoId);
        $eventBody = $this->project($videoDeleted, $eventId);

        unset($eventBody->modified);
        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'playhead' => 1,
                'completeness' => 12,
            ],
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_projects_updating_a_video_with_preserving_order_of_videos(): void
    {
        $eventId = 'e2ba2d94-af6b-48e8-a421-0bdd415ce381';
        $videoToUpdateId = '5c549a24-bb97-4f83-8ea5-21a6d56aff72';

        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'name' => [
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                    ],
                    (object)[
                        'id' => $videoToUpdateId,
                        'url' => 'https://vimeo.com/98765432',
                        'language' => 'nl',
                        'embedUrl' => 'https://player.vimeo.com/video/98765432',
                        'copyrightHolder' => 'Public Domain',
                    ],
                    (object)[
                        'id' => 'e86a4d89-1f95-475e-b637-b986f669ecef',
                        'url' => 'https://www.youtube.com/watch?v=4335',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Minds',
                        'embedUrl' => 'https://www.youtube.com/embed/4335',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $videoDeleted = new VideoUpdated(
            $eventId,
            (new Video(
                $videoToUpdateId,
                new Url('https://vimeo.com/123'),
                new Language('fr')
            ))->withCopyrightHolder(new CopyrightHolder('Modified copyrightHolder'))
        );
        $eventBody = $this->project($videoDeleted, $eventId);

        unset($eventBody->modified);
        $this->assertEquals(
            (object) [
                'name' => (object)[
                    'nl' => 'Titel',
                ],
                'videos' => [
                    (object)[
                        'id' => '91c75325-3830-4000-b580-5778b2de4548',
                        'url' => 'https://www.youtube.com/watch?v=123',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Commons',
                        'embedUrl' => 'https://www.youtube.com/embed/123',
                    ],
                    (object)[
                        'id' => $videoToUpdateId,
                        'url' => 'https://vimeo.com/123',
                        'language' => 'fr',
                        'embedUrl' => 'https://player.vimeo.com/video/123',
                        'copyrightHolder' => 'Modified copyrightHolder',
                    ],
                    (object)[
                        'id' => 'e86a4d89-1f95-475e-b637-b986f669ecef',
                        'url' => 'https://www.youtube.com/watch?v=4335',
                        'language' => 'nl',
                        'copyrightHolder' => 'Creative Minds',
                        'embedUrl' => 'https://www.youtube.com/embed/4335',
                    ],
                ],
                'playhead' => 1,
                'completeness' => 14,
            ],
            $eventBody
        );
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_the_organizer(): void
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-456';

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willThrowException(new DocumentDoesNotExist());

        $organizerUpdated = new OrganizerUpdated($id, $organizerId);

        $initialDocument = new JsonDocument(
            $id,
            Json::encode([
                'organizer' => [
                    '@type' => 'Organizer',
                    '@id' => 'http://example.com/organizers/ORGANIZER-ABC-123',
                ],
            ])
        );
        $this->documentRepository->save($initialDocument);

        $body = $this->project($organizerUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = (object)[
            'organizer' => (object)[
                '@type' => 'Organizer',
                '@id' => 'http://example.com/organizers/' . $organizerId,
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 3,
        ];

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_an_existing_organizer(): void
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-456';

        $this->organizerRepository->expects($this->once())
            ->method('fetch')
            ->with($organizerId)
            ->willReturnCallback(
                function ($argument) {
                    return new JsonDocument(
                        $argument,
                        Json::encode(['id' => $argument, 'name' => 'name'])
                    );
                }
            );

        $organizerUpdated = new OrganizerUpdated($id, $organizerId);

        $initialDocument = new JsonDocument($id);
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'organizer' => (object)[
                '@type' => 'Organizer',
                'id' => $organizerId,
                'name' => 'name',
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 3,
        ];

        $body = $this->project($organizerUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_deleting_of_the_organizer(): void
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-123';

        $organizerDeleted = new OrganizerDeleted($id, $organizerId);

        $initialDocument = new JsonDocument(
            $id,
            Json::encode([
                'organizer' => [
                    '@type' => 'Organizer',
                    '@id' => 'http://example.com/entity/' . $organizerId,
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($organizerDeleted, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object)[
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 0,
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_updates_available_from(): void
    {
        $itemId = '796bc3d1-1a07-463f-862f-45c07c9c3e28';

        $itemDocumentReadyDraft = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'workflowStatus' => 'DRAFT',
            ])
        );

        $expectedItem = (object)[
            '@id' => $itemId,
            '@type' => 'event',
            'availableFrom' => '2030-10-10T11:00:00+00:00',
            'workflowStatus' => 'DRAFT',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->documentRepository->save($itemDocumentReadyDraft);

        $approvedItem = $this->project(
            new AvailableFromUpdated(
                $itemId,
                new DateTimeImmutable('2030-10-10T11:00:00+00:00')
            ),
            $itemId,
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $this->assertEquals($expectedItem, $approvedItem);
    }

    /**
     * @test
     */
    public function it_updates_the_workflow_status_and_available_from_when_an_offer_is_published(): void
    {
        $itemId = 'a36f2c52-bd28-4f02-9c8e-d13ea7d16f7c';
        $now = new \DateTime();

        $publishedEvent = new Published($itemId, $now);
        $itemDocumentReadyDraft = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'workflowStatus' => 'DRAFT',
            ])
        );
        $expectedItem = (object)[
            '@id' => $itemId,
            '@type' => 'event',
            'availableFrom' => $now->format(DateTimeInterface::ATOM),
            'workflowStatus' => 'READY_FOR_VALIDATION',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->documentRepository->save($itemDocumentReadyDraft);

        $approvedItem = $this->project($publishedEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $approvedItem);
    }

    /**
     * @test
     */
    public function it_should_update_the_workflow_status_when_an_offer_is_approved(): void
    {
        $itemId = '19bc37e3-a8d7-400e-ab76-9c1ba51e6922';

        $approvedEvent = new Approved($itemId);
        $itemDocumentReadyForValidation = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'workflowStatus' => 'READY_FOR_VALIDATION',
            ])
        );
        $expectedItem = (object)[
            '@id' => $itemId,
            '@type' => 'event',
            'workflowStatus' => 'APPROVED',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->documentRepository->save($itemDocumentReadyForValidation);

        $approvedItem = $this->project($approvedEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $approvedItem);
    }


    /**
     * @test
     * @dataProvider rejectionEventsDataProvider
     */
    public function it_should_update_the_workflow_status_when_an_offer_is_rejected(
        string $itemId,
        AbstractEvent $rejectionEvent
    ): void {
        $itemDocumentReadyForValidation = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'workflowStatus' => 'READY_FOR_VALIDATION',
            ])
        );
        $expectedItem = (object)[
            '@id' => $itemId,
            '@type' => 'event',
            'workflowStatus' => 'REJECTED',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 0,
        ];

        $this->documentRepository->save($itemDocumentReadyForValidation);

        $rejectedItem = $this->project($rejectionEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $rejectedItem);
    }

    public function rejectionEventsDataProvider(): array
    {
        $itemId = '3f0bfdeb-3561-4b9b-b72e-1ccea8e489f8';

        return [
            'offer rejected' => [
                'itemId' => $itemId,
                'event' => new Rejected(
                    $itemId,
                    'Image contains nudity.'
                ),
            ],
            'offer flagged as duplicate' => [
                'itemId' => $itemId,
                'event' => new FlaggedAsDuplicate($itemId),
            ],
            'offer flagged as inappropriate' => [
                'itemId' => $itemId,
                'event' => new FlaggedAsInappropriate($itemId),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     */
    public function it_should_project_imported_udb2_media_files_as_media_objects(
        ImageCollection $images,
        array $expectedMediaObjects
    ): void {
        $itemId = '849dfa57-47d9-4d4f-878d-b2a0920ca6d3';
        $imagesImportedEvent = new ImagesImportedFromUDB2($itemId, $images);

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedMediaObjects, $importedItem->mediaObject);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     */
    public function it_should_project_updated_udb2_media_files_as_media_objects(
        ImageCollection $images,
        array $expectedMediaObjects
    ): void {
        $itemId = 'b25d7ae3-a1f9-4e57-b17d-ba9b3a3f3472';
        $imagesImportedEvent = new ImagesUpdatedFromUDB2($itemId, $images);

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedMediaObjects, $importedItem->mediaObject);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     */
    public function it_should_project_the_main_udb2_picture_as_image(
        ImageCollection $images
    ): void {
        $itemId = '85e0b800-cea4-4f8d-a70a-a55028bfcf6d';
        $imagesImportedEvent = new ImagesImportedFromUDB2($itemId, $images);
        $expectedImage = 'http://foo.bar/media/my_pic.jpg';

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedImage, $importedItem->image);
    }

    /**
     * @test
     */
    public function it_should_project_the_new_type_as_a_term_when_updated(): void
    {
        $itemId = '4e40acaa-f57e-4b82-b5d6-e772f8a1c2cf';
        $type = new EventType('YVBc8KVdrU6XfTNvhMYUpg', 'Discotheek');
        $typeUpdatedEvent = new TypeUpdated($itemId, $type);

        $expectedTerms = [
            (object) [
                'id' => 'YVBc8KVdrU6XfTNvhMYUpg',
                'label' => 'Discotheek',
                'domain' => 'eventtype',
            ],
        ];

        $updatedItem = $this->project($typeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }

    /**
     * @test
     */
    public function it_should_replace_the_existing_type_term_when_updating_with_a_new_type(): void
    {
        $itemId = 'f739f217-84e0-4c55-98e1-7f39ca7c62b5';
        $documentWithExistingTerms = new JsonDocument(
            $itemId,
            Json::encode([
                '@id' => $itemId,
                '@type' => 'event',
                'terms' => [
                    (object) [
                        'id' => '1.8.3.3.0',
                        'label' => 'Dance',
                        'domain' => 'theme',
                    ],
                    (object) [
                        'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                        'label' => 'Archeologische Site',
                        'domain' => 'eventtype',
                    ],
                ],
            ])
        );
        $type = new EventType('YVBc8KVdrU6XfTNvhMYUpg', 'Discotheek');
        $typeUpdatedEvent = new TypeUpdated($itemId, $type);

        $this->documentRepository->save($documentWithExistingTerms);

        $expectedTerms = [
            (object) [
                'id' => '1.8.3.3.0',
                'label' => 'Dance',
                'domain' => 'theme',
            ],
            (object) [
                'id' => 'YVBc8KVdrU6XfTNvhMYUpg',
                'label' => 'Discotheek',
                'domain' => 'eventtype',
            ],
        ];

        $updatedItem = $this->project($typeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_facilities(): void
    {
        $id = 'foo';
        $facilities = [
            new Facility('facility1', 'facility label'),
            new Facility('facility2', 'facility label2'),
        ];

        $facilitiesUpdated = new FacilitiesUpdated($id, $facilities);

        $initialDocument = new JsonDocument(
            $id,
            Json::encode(
                [
                    'name' => ['nl' => 'Foo'],
                    'terms' => [
                        [
                            'id' => 'facility1',
                            'label' => 'facility label',
                            'domain' => 'facility',
                        ],
                    ],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $expectedBody = (object) [
            'name' => (object) ['nl' => 'Foo'],
            'terms' => [
                (object) [
                    'id' => 'facility1',
                    'label' => 'facility label',
                    'domain' => 'facility',
                ],
                (object) [
                    'id' => 'facility2',
                    'label' => 'facility label2',
                    'domain' => 'facility',
                ],
            ],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 12,
        ];

        $body = $this->project($facilitiesUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_should_keep_images_translated_in_ubd3_when_updating_images_from_udb2(): void
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('ED5B9B25-8C16-48E5-9899-27BB2D110C57'),
            new MIMEType('image/jpg'),
            new Description('epische panorama foto'),
            new CopyrightHolder('Jean-François Millet'),
            new Url('http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg'),
            new LegacyLanguage('nl')
        );
        $expectedMediaObjects = [
            (object) [
                '@id' => 'http://example.com/entity/ED5B9B25-8C16-48E5-9899-27BB2D110C57',
                '@type' => 'schema:ImageObject',
                'id' => 'ED5B9B25-8C16-48E5-9899-27BB2D110C57',
                'contentUrl' => 'http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg',
                'thumbnailUrl' => 'http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg',
                'description' => 'epische panorama foto',
                'copyrightHolder' => 'Jean-François Millet',
                'inLanguage' => 'nl',
            ],
            (object) [
                '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                '@type' => 'schema:ImageObject',
                'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'description' => 'The Gleaners',
                'copyrightHolder' => 'Jean-François Millet',
                'inLanguage' => 'en',
            ],
        ];
        $initialDocument = new JsonDocument(
            $eventId,
            Json::encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    (object) [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'The Gleaners',
                        'copyrightHolder' => 'Jean-François Millet',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageUpdatedEvent = new ImagesUpdatedFromUDB2($eventId, ImageCollection::fromArray([$image]));
        $eventBody = $this->project($imageUpdatedEvent, $eventId);

        $this->assertEquals(
            $expectedMediaObjects,
            $eventBody->mediaObject
        );
    }

    public function imageCollectionDataProvider(): array
    {
        $coverPicture = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/my_pic.jpg'),
            new LegacyLanguage('en')
        );

        $selfie = new Image(
            new UUID('e56e8eb6-dcd7-47e7-8106-8a149f1d241b'),
            new MIMEType('image/jpg'),
            new Description('my favorite selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            new Url('http://foo.bar/media/img_182.jpg'),
            new LegacyLanguage('en')
        );

        return [
            'single image' => [
                'imageCollection' => ImageCollection::fromArray([$coverPicture, $selfie]),
                'expectedMediaObjects' => [
                    (object)[
                        '@type' => 'schema:ImageObject',
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                        'contentUrl' => 'http://foo.bar/media/my_pic.jpg',
                        'thumbnailUrl' => 'http://foo.bar/media/my_pic.jpg',
                        'description' => 'my pic',
                        'copyrightHolder' => 'Dirk Dirkington',
                        'inLanguage' => 'en',
                    ],
                    (object)[
                        '@type' => 'schema:ImageObject',
                        '@id' => 'http://example.com/entity/e56e8eb6-dcd7-47e7-8106-8a149f1d241b',
                        'id' => 'e56e8eb6-dcd7-47e7-8106-8a149f1d241b',
                        'contentUrl' => 'http://foo.bar/media/img_182.jpg',
                        'thumbnailUrl' => 'http://foo.bar/media/img_182.jpg',
                        'description' => 'my favorite selfie',
                        'copyrightHolder' => 'Dirk Dirkington',
                        'inLanguage' => 'en',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_remove_bookinginfo_when_array_is_empty(): void
    {
        $id = 'e56e8eb6-dcd7-47e7-8106-8a149f1d241b';

        $initialDocument = new JsonDocument(
            $id,
            Json::encode(
                [
                    'name' => ['nl' => 'Foo'],
                    'bookingInfo' => [

                    ],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'name' => (object)['nl' => 'Foo'],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 12,
        ];

        $event = new BookingInfoUpdated($id, new BookingInfo());

        $body = $this->project($event, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_should_update_bookinginfo(): void
    {
        $id = 'e56e8eb6-dcd7-47e7-8106-8a149f1d241b';

        $initialDocument = new JsonDocument(
            $id,
            Json::encode(
                [
                    'name' => ['nl' => 'Foo'],
                    'bookingInfo' => [

                    ],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'name' => (object)['nl' => 'Foo'],
            'bookingInfo' => (object)[
                'phone' => '0471123456',
                'email' => 'test@test.be',
                'url' => 'http://www.google.be',
                'urlLabel' => (object)['nl' => 'Dit is een booking info event'],
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 15,
        ];

        $event = new BookingInfoUpdated($id, new BookingInfo(
            'http://www.google.be',
            new MultilingualString(new LegacyLanguage('nl'), 'Dit is een booking info event'),
            '0471123456',
            'test@test.be'
        ));

        $body = $this->project($event, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_updates_the_playhead_on_concluded(): void
    {
        $itemId = 'C50051D6-EEB1-E9F9-B07889755901D7156';
        $initialDocument = new JsonDocument(
            $itemId,
            Json::encode([])
        );

        $this->documentRepository->save($initialDocument);

        $concluded = new Concluded($itemId);

        $body = $this->project($concluded, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object) [
                'modified' => $this->recordedOn->toString(),
                'playhead' => 1,
                'completeness' => 0,
            ],
            $body
        );
    }
}
