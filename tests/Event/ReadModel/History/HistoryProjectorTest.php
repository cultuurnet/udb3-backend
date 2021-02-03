<?php

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\Concluded;
use CultuurNet\UDB3\Event\Events\ContactPointUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Event\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Event\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved;
use CultuurNet\UDB3\Event\Events\ImageUpdated;
use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\Event\Events\LabelsImported;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MainImageSelected;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\OrganizerDeleted;
use CultuurNet\UDB3\Event\Events\OrganizerUpdated;
use CultuurNet\UDB3\Event\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Event\Events\ThemeUpdated;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\Events\TypeUpdated;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Event\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class HistoryProjectorTest extends TestCase
{
    private const EVENT_ID_1 = 'a0ee7b1c-a9c1-4da1-af7e-d15496014656';
    private const EVENT_ID_2 = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';

    private const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    /**
     * @var HistoryProjector
     */
    protected $historyProjector;

    /**
     * @var DocumentRepository
     */
    protected $documentRepository;

    public function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->historyProjector = new HistoryProjector(
            $this->documentRepository
        );

        $eventImported = new EventImportedFromUDB2(
            self::EVENT_ID_1,
            $this->getEventCdbXml(self::EVENT_ID_1),
            self::CDBXML_NAMESPACE
        );

        $importedDate = '2015-03-04T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventImported->getEventId(),
            1,
            new Metadata(),
            $eventImported,
            DateTime::fromString($importedDate)
        );

        $this->historyProjector->handle($domainMessage);
    }

    /**
     * @param string $eventId
     * @return string
     */
    protected function getEventCdbXml($eventId)
    {
        return file_get_contents(__DIR__ . '/event-' . $eventId . '.xml');
    }

    /**
     * @test
     */
    public function it_logs_EventImportedFromUDB2()
    {
        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Event geïmporteerd uit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Event aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );

        $eventImported = new EventImportedFromUDB2(
            self::EVENT_ID_2,
            $this->getEventCdbXml(self::EVENT_ID_2),
            self::CDBXML_NAMESPACE
        );

        $importedDate = '2015-03-01T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventImported->getEventId(),
            1,
            new Metadata(),
            $eventImported,
            DateTime::fromString($importedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_2,
            [
                (object) [
                    'date' => '2015-03-01T10:17:19+02:00',
                    'description' => 'Event geïmporteerd uit UDB2',
                ],
                (object) [
                    'date' => '2014-09-08T09:10:16+02:00',
                    'description' => 'Event aangemaakt in UDB2',
                    'author' => 'info@traeghe.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_EventUpdatedFromUDB2()
    {
        $eventUpdated = new EventUpdatedFromUDB2(
            self::EVENT_ID_1,
            $this->getEventCdbXml(self::EVENT_ID_1),
            self::CDBXML_NAMESPACE
        );

        $updatedDate = '2015-03-25T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventUpdated->getEventId(),
            2,
            new Metadata(),
            $eventUpdated,
            DateTime::fromString($updatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'description' => 'Event aangepast via UDB2',
                    'date' => '2015-03-25T10:17:19+02:00',
                ],
                (object) [
                    'date' => '2015-03-04T10:17:19+02:00',
                    'description' => 'Event geïmporteerd uit UDB2',
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Event aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_creating_an_event()
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            new Title('Faith no More'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            new Metadata(
                [
                    'user_nick' => 'Jan Janssen',
                    'auth_api_key' => 'my-super-duper-key',
                    'api' => 'json-api',
                    'consumer' => [
                        'name' => 'My super duper name',
                    ],
                ]
            ),
            $eventCreated,
            DateTime::fromString($now->format(\DateTime::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) [
                    'date' => $now->format('c'),
                    'author' => 'Jan Janssen',
                    'description' => 'Event aangemaakt in UiTdatabank',
                    'apiKey' => 'my-super-duper-key',
                    'api' => 'json-api',
                    'consumerName' => 'My super duper name',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_copying_an_event()
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';
        $originalEventId = '1fd05542-ce0b-4ed1-ad17-cf5a0f316da4';

        $eventCopied = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::PERMANENT())
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $eventCopied,
            DateTime::fromString($now->format(\DateTime::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) [
                    'date' => $now->format('c'),
                    'author' => 'Jan Janssen',
                    'description' => 'Event gekopieerd van ' . $originalEventId,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_titleTranslated()
    {
        $titleTranslated = new TitleTranslated(
            self::EVENT_ID_1,
            new Language('fr'),
            new Title('Titre en français')
        );

        $translatedDate = '2015-03-26T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $titleTranslated->getItemId(),
            3,
            new Metadata(['user_nick' => 'JohnDoe']),
            $titleTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-26T10:17:19+02:00',
                    'author' => 'JohnDoe',
                    'description' => 'Titel vertaald (fr)',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_descriptionTranslated()
    {
        $descriptionTranslated = new DescriptionTranslated(
            self::EVENT_ID_1,
            new Language('fr'),
            new Description('Signalement en français')
        );

        $translatedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $descriptionTranslated->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $descriptionTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Beschrijving vertaald (fr)',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_eventWasTagged()
    {
        $eventWasTagged = new LabelAdded(
            self::EVENT_ID_1,
            new Label('foo')
        );

        $taggedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventWasTagged->getItemId(),
            2,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $eventWasTagged,
            DateTime::fromString($taggedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'Jan Janssen',
                    'description' => "Label 'foo' toegepast",
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_tagErased()
    {
        $tagErased = new LabelRemoved(
            self::EVENT_ID_1,
            new Label('foo')
        );

        $tagErasedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $tagErased->getItemId(),
            2,
            new Metadata(['user_nick' => 'Jan Janssen']),
            $tagErased,
            DateTime::fromString($tagErasedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'Jan Janssen',
                    'description' => "Label 'foo' verwijderd",
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_approved(): void
    {
        $event = new Approved(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Goedgekeurd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_audience_updated(): void
    {
        $event = new AudienceUpdated(self::EVENT_ID_1, new Audience(AudienceType::EDUCATION()));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Toegang aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_booking_info_updated(): void
    {
        $event = new BookingInfoUpdated(self::EVENT_ID_1, new BookingInfo());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Reservatie-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_calendar_updated(): void
    {
        $event = new CalendarUpdated(self::EVENT_ID_1, new Calendar(CalendarType::PERMANENT()));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Kalender-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_concluded(): void
    {
        $event = new Concluded(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Event afgelopen',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_contact_point_updated(): void
    {
        $event = new ContactPointUpdated(self::EVENT_ID_1, new ContactPoint());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Contact-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_description_updated(): void
    {
        $event = new DescriptionUpdated(self::EVENT_ID_1, new Description('new'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Beschrijving aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_event_deleted(): void
    {
        $event = new EventDeleted(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Event verwijderd uit UiTdatabank',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_facilities_updated(): void
    {
        $event = new FacilitiesUpdated(self::EVENT_ID_1, []);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Voorzieningen aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_flagged_as_duplicate(): void
    {
        $event = new FlaggedAsDuplicate(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afgekeurd als duplicaat',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_flagged_as_inappropriate(): void
    {
        $event = new FlaggedAsInappropriate(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afgekeurd als ongepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_geo_coordinates_updated(): void
    {
        $event = new GeoCoordinatesUpdated(
            self::EVENT_ID_1,
            new Coordinates(
                new Latitude(0.0),
                new Longitude(0.0)
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Geocoördinaten automatisch aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_image_added(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageAdded(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' toegevoegd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_image_removed(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageRemoved(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' verwijderd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_image_updated(): void
    {
        $event = new ImageUpdated(
            self::EVENT_ID_1,
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder')
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_images_imported_from_udb2(): void
    {
        $image1 = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test1.jpg'),
            new Language('en')
        );

        $image2 = new Image(
            new UUID('f1926870-136c-4b06-b2a1-1fab01590847'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test2.jpg'),
            new Language('en')
        );

        $event = new ImagesImportedFromUDB2(
            self::EVENT_ID_1,
            (new ImageCollection())
                ->with($image1)
                ->with($image2)
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' geïmporteerd uit UDB2',
                ],
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' geïmporteerd uit UDB2',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_images_updated_from_udb2(): void
    {
        $image1 = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test1.jpg'),
            new Language('en')
        );

        $image2 = new Image(
            new UUID('f1926870-136c-4b06-b2a1-1fab01590847'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test2.jpg'),
            new Language('en')
        );

        $event = new ImagesUpdatedFromUDB2(
            self::EVENT_ID_1,
            (new ImageCollection())
                ->with($image1)
                ->with($image2)
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast via UDB2',
                ],
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' aangepast via UDB2',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_labels_imported(): void
    {
        $event = new LabelsImported(self::EVENT_ID_1, new Labels());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Labels geïmporteerd uit JSON-LD',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_location_updated(): void
    {
        $event = new LocationUpdated(self::EVENT_ID_1, new LocationId('827b7d8d-8821-4870-a48b-bea9d44f557c'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => "Locatie aangepast naar '827b7d8d-8821-4870-a48b-bea9d44f557c'",
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_main_image_selected(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new MainImageSelected(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Hoofdafbeelding geselecteerd: \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\'',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_major_info_updated(): void
    {
        $event = new MajorInfoUpdated(
            self::EVENT_ID_1,
            new Title('title'),
            new EventType('0.0.0.0', 'event type'),
            new LocationId('a0c6c66e-d933-4817-a335-2a5a51df1fa7'),
            new Calendar(CalendarType::PERMANENT())
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'MajorInfo aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_organizer_deleted(): void
    {
        $event = new OrganizerDeleted(
            self::EVENT_ID_1,
            '0d7d2247-ebaa-4ff0-baf9-8ea274579cc3'
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Organisatie \'0d7d2247-ebaa-4ff0-baf9-8ea274579cc3\' verwijderd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_organizer_updated(): void
    {
        $event = new OrganizerUpdated(
            self::EVENT_ID_1,
            '0d7d2247-ebaa-4ff0-baf9-8ea274579cc3'
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Organisatie \'0d7d2247-ebaa-4ff0-baf9-8ea274579cc3\' toegevoegd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_price_info_updated(): void
    {
        $event = new PriceInfoUpdated(
            self::EVENT_ID_1,
            new PriceInfo(
                new BasePrice(
                    Price::fromFloat(10.0),
                    Currency::fromNative('EUR')
                )
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Prijs-info aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_published(): void
    {
        $event = new Published(
            self::EVENT_ID_1,
            DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                '2015-04-30T02:00:00+02:00'
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Gepubliceerd (publicatiedatum: \'2015-04-30T02:00:00+02:00\')',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_rejected(): void
    {
        $event = new Rejected(self::EVENT_ID_1, new StringLiteral('not good enough'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => "Afgekeurd, reden: 'not good enough'",
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_theme_updated(): void
    {
        $event = new ThemeUpdated(self::EVENT_ID_1, new Theme('0.1', 'theme label'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Thema aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_title_updated(): void
    {
        $event = new TitleUpdated(self::EVENT_ID_1, new Title('new title'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Titel aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_type_updated(): void
    {
        $event = new TypeUpdated(self::EVENT_ID_1, new EventType('0.1.1', 'type label'));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Type aangepast',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_typical_age_range_deleted(): void
    {
        $event = new TypicalAgeRangeDeleted(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Leeftijds-info verwijderd',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_typical_age_range_updated(): void
    {
        $event = new TypicalAgeRangeUpdated(self::EVENT_ID_1, new AgeRange());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_nick' => 'JaneDoe']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => 'JaneDoe',
                    'description' => 'Leeftijds-info aangepast',
                ],
            ]
        );
    }

    protected function assertHistoryContainsLogs(string $eventId, array $history): void
    {
        /** @var JsonDocument $document */
        $document = $this->documentRepository->get($eventId);
        $body = array_values((array) $document->getBody());

        $body = array_map(function (\stdClass $log) {
            return (array) $log;
        }, $body);

        foreach ($history as $log) {
            $this->assertContains((array) $log, $body);
        }
    }

    /**
     * @param string $userNick
     * @param string $consumerName
     * @return Metadata
     */
    protected function entryApiMetadata($userNick, $consumerName)
    {
        $values = [
            'user_nick' => $userNick,
            'consumer' => [
                'name' => $consumerName,
            ],
        ];

        return new Metadata($values);
    }
}
