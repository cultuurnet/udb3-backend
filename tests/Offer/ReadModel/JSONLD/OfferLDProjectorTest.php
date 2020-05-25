<?php

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Facility;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ThemeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\ReadModel\JSONLD\ItemLDProjector;
use CultuurNet\UDB3\OrganizerService;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\PriceInfo\Tariff;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentNullEnricher;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class OfferLDProjectorTest extends TestCase
{
    /**
     * @var InMemoryDocumentRepository
     */
    protected $documentRepository;

    /**
     * @var ItemLDProjector
     */
    protected $projector;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var OrganizerService|MockObject
     */
    protected $organizerService;

    /**
     * @var MediaObjectSerializer
     */
    protected $serializer;

    /**
     * @var RecordedOn
     */
    protected $recordedOn;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerService = $this->createMock(OrganizerService::class);

        $this->iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->serializer = new MediaObjectSerializer($this->iriGenerator);

        $this->projector = new ItemLDProjector(
            $this->documentRepository,
            $this->iriGenerator,
            $this->organizerService,
            $this->serializer,
            new JsonDocumentNullEnricher(),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ]
        );

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-01T08:30:00+0100')
        );
    }

    /**
     * @param object $event
     * @param string $entityId
     * @param Metadata|null $metadata
     * @param DateTime $dateTime
     * @return \stdClass
     */
    protected function project(
        $event,
        $entityId,
        Metadata $metadata = null,
        DateTime $dateTime = null
    ) {
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

    /**
     * @param string $id
     * @return \stdClass
     */
    protected function getBody($id)
    {
        $document = $this->documentRepository->get($id);
        return $document->getBody();
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label()
    {
        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
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
    public function it_projects_the_addition_of_an_invisible_label()
    {
        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B', false)
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
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
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_removal_of_a_label()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'labels' => ['label A', 'label B', 'label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            new Label('label B')
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
    public function it_projects_the_removal_of_a_hidden_label()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'labels' => ['label A', 'label B'],
                'hiddenLabels' => ['label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            new Label('label C', false)
        );

        $body = $this->project($labelRemoved, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals(
            (object) [
                'labels' => ['label A', 'label B'],
                'modified' => $this->recordedOn->toString(),
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label_to_an_event_without_existing_labels()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'bar' => 'stool',
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = new stdClass();
        $expectedBody->bar = 'stool';
        $expectedBody->labels = ['label B'];
        $expectedBody->modified = $this->recordedOn->toString();

        $this->assertEquals(
            $expectedBody,
            $body
        );

    }

    /**
     * @test
     */
    public function it_should_update_the_main_language_name_property_when_a_title_updated_event_occurs()
    {
        $titleUpdatedEvent = new TitleUpdated(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            new Title('A cycling adventure')
        );

        $initialDocument = new JsonDocument(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            json_encode([
                'mainLanguage' => 'en',
                'name' => [
                    'nl'=> 'Fietsen langs kapelletjes',
                    'en'=> 'Cycling through Flanders',
                ],
            ])
        );

        $expectedDocument = new JsonDocument(
            '5582FCA5-38FD-40A0-B8FB-9FA70AB7ADA3',
            json_encode([
                'mainLanguage' => 'en',
                'name' => [
                    'nl'=> 'Fietsen langs kapelletjes',
                    'en'=> 'A cycling adventure',
                ],
                'modified' => $this->recordedOn->toString(),
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
    public function it_projects_the_translation_of_the_title()
    {
        $titleTranslated = new TitleTranslated(
            'foo',
            new Language('en'),
            new Title('English title')
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
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
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_translation_of_the_description()
    {
        $descriptionTranslated = new DescriptionTranslated(
            'foo',
            new Language('en'),
            new \CultuurNet\UDB3\Description('English description')
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
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
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_updated_price_info()
    {
        $aggregateId = 'a5bafa9d-a71e-4624-835d-57db2832a7d8';

        $priceInfo = new PriceInfo(
            new BasePrice(
                Price::fromFloat(10.5),
                Currency::fromNative('EUR')
            )
        );

        $priceInfo = $priceInfo->withExtraTariff(
            new Tariff(
                new MultilingualString(
                    new Language('nl'),
                    new StringLiteral('Werkloze dodo kwekers')
                ),
                new Price(0),
                Currency::fromNative('EUR')
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
                    'name' => (object) ['nl' => 'Werkloze dodo kwekers'],
                    'price' => 0,
                    'priceCurrency' => 'EUR',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $this->documentRepository->save($initialDocument);

        $actualBody = $this->project($priceInfoUpdated, $aggregateId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $actualBody);
    }

    /**
     * @test
     */
    public function it_adds_a_media_object_when_an_image_is_added_to_the_event()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $expectedMediaObjects = [
            (object) [
                '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'description' => 'sexy ladies without clothes',
                'copyrightHolder' => 'Bart Ramakers',
                'inLanguage' => 'en',
            ],
        ];
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
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

    public function mediaObjectDataProvider()
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
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
                        'inLanguage' => 'en',
                    ],
                ],
            ];

        $image1 = new Image(
            new UUID('de305d54-ddde-eddd-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('my best pokerface'),
            new CopyrightHolder('Hans Langucci'),
            Url::fromNative(
                'http://foo.bar/media/de305d54-ddde-eddd-adb2-eb6b9e546014.png'
            ),
            new Language('en')
        );

        $image2 = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative(
                'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'
            ),
            new Language('en')
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
        ];

        $expectedWithoutFirstImage = (object) [
            'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            'mediaObject' => [
                (object) [
                    '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'description' => 'sexy ladies without clothes',
                    'copyrightHolder' => 'Bart Ramakers',
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => '2018-01-01T08:30:00+01:00',
        ];


        return [
            'document with 2 images, last image gets removed' => [
                new JsonDocument(
                    $eventId,
                    json_encode((object) $initialJsonStructureWithMedia)
                ),
                $image2,
                $expectedWithoutLastImage,
            ],
            'document with 2 images, first image gets removed' => [
                new JsonDocument(
                    $eventId,
                    json_encode((object) $initialJsonStructureWithMedia)
                ),
                $image1,
                $expectedWithoutFirstImage,
            ],
            'document without media' => [
                new JsonDocument(
                    $eventId,
                    json_encode((object) $initialJsonStructure)
                ),
                $image1,
                (object) $initialJsonStructure,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider mediaObjectDataProvider
     * @param JsonDocument $initialDocument
     * @param Image $image
     * @param $expectedProjection
     */
    public function it_should_remove_the_media_object_of_an_image(
        JsonDocument $initialDocument,
        Image $image,
        $expectedProjection
    ) {
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
    public function it_should_destroy_the_media_object_attribute_when_no_media_objects_are_left_after_removing_an_image()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertObjectNotHasAttribute('mediaObject', $eventBody);
    }

    /**
     * @test
     */
    public function it_should_unset_the_main_image_when_its_media_object_is_removed()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);
        $imageRemovedEvent = new ImageRemoved($eventId, $image);
        $eventBody = $this->project($imageRemovedEvent, $eventId);

        $this->assertObjectNotHasAttribute('image', $eventBody);
    }

    /**
     * @test
     */
    public function it_should_make_an_image_main_when_added_to_an_item_without_existing_ones()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
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
    public function it_should_make_the_oldest_image_main_when_deleting_the_current_main_image()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Bart Ramakers',
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
    public function it_should_set_the_image_property_when_selecting_a_main_image()
    {
        $eventId = 'event-1';
        $selectedMainImage = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/png'),
            new Description('sexy ladies without clothes'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png'),
            new Language('en')
        );
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
                'image' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
                        'inLanguage' => 'en',
                    ],
                    [
                        '@id' => 'http://example.com/entity/5ae74e68-20a3-4cb1-b255-8e405aa01ab9',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'thumbnailUrl' => 'http://foo.bar/media/5ae74e68-20a3-4cb1-b255-8e405aa01ab9.png',
                        'description' => 'funny giphy image',
                        'copyrightHolder' => 'Bart Ramakers',
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
    public function it_projects_the_updating_of_the_organizer()
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-456';

        $this->organizerService->expects($this->once())
            ->method('getEntity')
            ->with($organizerId)
            ->willThrowException(new EntityNotFoundException());
        $this->organizerService->expects($this->once())
            ->method('iri')
            ->willReturnCallback(
                function ($argument) {
                    return 'http://example.com/entity/' . $argument;
                }
            );

        $organizerUpdated = new OrganizerUpdated($id, $organizerId);

        $initialDocument = new JsonDocument(
            $id,
            json_encode([
                'organizer' => [
                    '@type' => 'Organizer',
                    '@id' => 'http://example.com/entity/ORGANIZER-ABC-123',
                ],
            ])
        );
        $this->documentRepository->save($initialDocument);

        $body = $this->project($organizerUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = (object)[
            'organizer' => (object)[
                '@type' => 'Organizer',
                '@id' => 'http://example.com/entity/' . $organizerId,
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_an_existing_organizer()
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-456';

        $this->organizerService->expects($this->once())
            ->method('getEntity')
            ->with($organizerId)
            ->willReturnCallback(
                function ($argument) {
                    return json_encode(['id' => $argument, 'name' => 'name']);
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
        ];

        $body = $this->project($organizerUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_deleting_of_the_organizer()
    {
        $id = 'foo';
        $organizerId = 'ORGANIZER-ABC-123';

        $organizerDeleted = new OrganizerDeleted($id, $organizerId);

        $initialDocument = new JsonDocument(
            $id,
            json_encode([
                'organizer' => [
                    '@type' => 'Organizer',
                    '@id' => 'http://example.com/entity/' . $organizerId,
                ],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($organizerDeleted, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals((object)['modified' => $this->recordedOn->toString()], $body);
    }

    /**
     * @test
     */
    public function it_updates_the_workflow_status_and_available_from_when_an_offer_is_published()
    {
        $itemId = UUID::generateAsString();
        $now = new \DateTime();

        $publishedEvent = new Published($itemId, $now);
        $itemDocumentReadyDraft = new JsonDocument(
            $itemId,
            json_encode([
                '@id' => $itemId,
                '@type' => 'event',
                'workflowStatus' => 'DRAFT',
            ])
        );
        $expectedItem = (object)[
            '@id' => $itemId,
            '@type' => 'event',
            'availableFrom' => $now->format(\DateTime::ATOM),
            'workflowStatus' => 'READY_FOR_VALIDATION',
            'modified' => $this->recordedOn->toString(),
        ];

        $this->documentRepository->save($itemDocumentReadyDraft);

        $approvedItem = $this->project($publishedEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $approvedItem);
    }

    /**
     * @test
     */
    public function it_should_update_the_workflow_status_when_an_offer_is_approved()
    {
        $itemId = UUID::generateAsString();

        $approvedEvent = new Approved($itemId);
        $itemDocumentReadyForValidation = new JsonDocument(
            $itemId,
            json_encode([
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
        ];

        $this->documentRepository->save($itemDocumentReadyForValidation);

        $approvedItem = $this->project($approvedEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $approvedItem);
    }


    /**
     * @test
     * @dataProvider rejectionEventsDataProvider
     * @param string $itemId
     * @param AbstractEvent $rejectionEvent
     */
    public function it_should_update_the_workflow_status_when_an_offer_is_rejected(
        $itemId,
        AbstractEvent $rejectionEvent
    ) {
        $itemDocumentReadyForValidation = new JsonDocument(
            $itemId,
            json_encode([
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
        ];

        $this->documentRepository->save($itemDocumentReadyForValidation);

        $rejectedItem = $this->project($rejectionEvent, $itemId, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedItem, $rejectedItem);
    }

    /**
     * @return array
     */
    public function rejectionEventsDataProvider()
    {
        $itemId = UUID::generateAsString();

        return [
            'offer rejected' => [
                'itemId' => $itemId,
                'event' => new Rejected(
                    $itemId,
                    new StringLiteral('Image contains nudity.')
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
     * @param ImageCollection $images
     * @param $expectedMediaObjects
     */
    public function it_should_project_imported_udb2_media_files_as_media_objects(
        ImageCollection $images,
        $expectedMediaObjects
    ) {
        $itemId = UUID::generateAsString();
        $imagesImportedEvent = new ImagesImportedFromUDB2($itemId, $images);

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedMediaObjects, $importedItem->mediaObject);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     * @param ImageCollection $images
     * @param $expectedMediaObjects
     */
    public function it_should_project_updated_udb2_media_files_as_media_objects(
        ImageCollection $images,
        $expectedMediaObjects
    ) {
        $itemId = UUID::generateAsString();
        $imagesImportedEvent = new ImagesUpdatedFromUDB2($itemId, $images);

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedMediaObjects, $importedItem->mediaObject);
    }

    /**
     * @test
     * @dataProvider imageCollectionDataProvider
     * @param ImageCollection $images
     */
    public function it_should_project_the_main_udb2_picture_as_image(
        ImageCollection $images
    ) {
        $itemId = UUID::generateAsString();
        $imagesImportedEvent = new ImagesImportedFromUDB2($itemId, $images);
        $expectedImage = 'http://foo.bar/media/my_pic.jpg';

        $importedItem = $this->project($imagesImportedEvent, $itemId);
        $this->assertEquals($expectedImage, $importedItem->image);
    }

    /**
     * @test
     */
    public function it_should_project_the_new_type_as_a_term_when_updated()
    {
        $itemId = UUID::generateAsString();
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
    public function it_should_replace_the_existing_type_term_when_updating_with_a_new_type()
    {
        $itemId = UUID::generateAsString();
        $documentWithExistingTerms = new JsonDocument(
            $itemId,
            json_encode([
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
    public function it_should_project_the_new_theme_as_a_term_when_updated()
    {
        $itemId = UUID::generateAsString();
        $theme = new Theme('1.8.3.3.0', 'Dance');
        $themeUpdatedEvent = new ThemeUpdated($itemId, $theme);

        $expectedTerms = [
            (object) [
                'id' => '1.8.3.3.0',
                'label' => 'Dance',
                'domain' => 'theme',
            ],
        ];

        $updatedItem = $this->project($themeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }

    /**
     * @test
     */
    public function it_should_replace_the_existing_theme_term_when_updating_with_a_new_theme()
    {
        $itemId = UUID::generateAsString();
        $documentWithExistingTerms = new JsonDocument(
            $itemId,
            json_encode([
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
        $theme = new Theme('1.8.2.0.0', 'Jazz en booze');
        $themeUpdatedEvent = new ThemeUpdated($itemId, $theme);

        $this->documentRepository->save($documentWithExistingTerms);

        $expectedTerms = [
            (object) [
                'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                'label' => 'Archeologische Site',
                'domain' => 'eventtype',
            ],
            (object) [
                'id' => '1.8.2.0.0',
                'label' => 'Jazz en booze',
                'domain' => 'theme',
            ],
        ];

        $updatedItem = $this->project($themeUpdatedEvent, $itemId);
        $this->assertEquals($expectedTerms, $updatedItem->terms);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_facilities()
    {
        $id = 'foo';
        $facilities = [
            new Facility('facility1', 'facility label'),
            new Facility('facility2', 'facility label2'),
        ];

        $facilitiesUpdated = new FacilitiesUpdated($id, $facilities);

        $initialDocument = new JsonDocument(
            $id,
            json_encode(
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
        ];

        $body = $this->project($facilitiesUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_should_keep_images_translated_in_ubd3_when_updating_images_from_udb2()
    {
        $eventId = 'event-1';
        $image = new Image(
            new UUID('ED5B9B25-8C16-48E5-9899-27BB2D110C57'),
            new MIMEType('image/jpg'),
            new Description('epische panorama foto'),
            new CopyrightHolder('Bart Ramakers'),
            Url::fromNative('http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg'),
            new Language('nl')
        );
        $expectedMediaObjects = [
            (object) [
                '@id' => 'http://example.com/entity/ED5B9B25-8C16-48E5-9899-27BB2D110C57',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg',
                'thumbnailUrl' => 'http://foo.bar/media/ED5B9B25-8C16-48E5-9899-27BB2D110C57.jpg',
                'description' => 'epische panorama foto',
                'copyrightHolder' => 'Bart Ramakers',
                'inLanguage' => 'nl',
            ],
            (object) [
                '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'description' => 'sexy ladies without clothes',
                'copyrightHolder' => 'Bart Ramakers',
                'inLanguage' => 'en',
            ],
        ];
        $initialDocument = new JsonDocument(
            $eventId,
            json_encode([
                'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                'mediaObject' => [
                    (object) [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'sexy ladies without clothes',
                        'copyrightHolder' => 'Bart Ramakers',
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

    public function imageCollectionDataProvider()
    {
        $coverPicture = new Image(
            new UUID('de305d54-75b4-431b-adb2-eb6b9e546014'),
            new MIMEType('image/jpg'),
            new Description('my pic'),
            new CopyrightHolder('Dirk Dirkington'),
            Url::fromNative('http://foo.bar/media/my_pic.jpg'),
            new Language('en')
        );

        $selfie = new Image(
            new UUID('e56e8eb6-dcd7-47e7-8106-8a149f1d241b'),
            new MIMEType('image/jpg'),
            new Description('my favorite selfie'),
            new CopyrightHolder('Dirk Dirkington'),
            Url::fromNative('http://foo.bar/media/img_182.jpg'),
            new Language('en')
        );

        return [
            'single image' => [
                'imageCollection' => ImageCollection::fromArray([$coverPicture, $selfie]),
                'expectedMediaObjects' => [
                    (object)[
                        '@type' => 'schema:ImageObject',
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        'contentUrl' => 'http://foo.bar/media/my_pic.jpg',
                        'thumbnailUrl' => 'http://foo.bar/media/my_pic.jpg',
                        'description' => 'my pic',
                        'copyrightHolder' => 'Dirk Dirkington',
                        'inLanguage' => 'en',
                    ],
                    (object)[
                        '@type' => 'schema:ImageObject',
                        '@id' => 'http://example.com/entity/e56e8eb6-dcd7-47e7-8106-8a149f1d241b',
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
}
