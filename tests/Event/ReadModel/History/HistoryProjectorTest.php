<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\Events\AttendanceModeUpdated;
use CultuurNet\UDB3\Event\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Event\Events\OnlineUrlDeleted;
use CultuurNet\UDB3\Event\Events\OnlineUrlUpdated;
use CultuurNet\UDB3\Event\Events\ThemeRemoved;
use CultuurNet\UDB3\Event\Events\VideoAdded;
use CultuurNet\UDB3\Event\Events\VideoDeleted;
use CultuurNet\UDB3\Event\Events\VideoUpdated;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Events\AudienceUpdated;
use CultuurNet\UDB3\Event\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
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
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Contact\ContactPoint;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\SampleFiles;
use CultuurNet\UDB3\Theme;
use DateTimeImmutable;
use DateTimeInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class HistoryProjectorTest extends TestCase
{
    private const EVENT_ID_1 = 'a0ee7b1c-a9c1-4da1-af7e-d15496014656';
    private const EVENT_ID_2 = 'a2d50a8d-5b83-4c8b-84e6-e9c0bacbb1a3';

    private const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

    protected HistoryProjector $historyProjector;

    protected DocumentRepository $documentRepository;

    public function setUp(): void
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

    protected function getEventCdbXml(string $eventId): string
    {
        return SampleFiles::read(__DIR__ . '/event-' . $eventId . '.xml');
    }

    /**
     * @test
     */
    public function it_logs_EventImportedFromUDB2(): void
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
    public function it_logs_EventUpdatedFromUDB2(): void
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
                    'date' => '2015-03-25T10:17:19+02:00',
                    'description' => 'Event aangepast via UDB2',
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

    public function metadataProvider(): array
    {
        return [
            'with user id' => [
                new Metadata(['user_id' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6']),
                ['author' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6'],
            ],
            'with api' => [
                new Metadata(['api' => 'json-api']),
                ['api' => 'json-api'],
            ],
            'with api key' => [
                new Metadata(['auth_api_key' => 'my-super-duper-key']),
                ['apiKey' => 'my-super-duper-key'],
            ],
            'with consumer name' => [
                new Metadata(['consumer' => ['name' => 'My super duper name']]),
                ['consumerName' => 'My super duper name'],
            ],
            'with auth0 client id' => [
                new Metadata(['auth_api_client_id' => 'my-auth0-client-id']),
                ['auth0ClientId' => 'my-auth0-client-id'],
            ],
            'with auth0 client name' => [
                new Metadata(['auth_api_client_name' => 'My Auth0 Client']),
                ['auth0ClientName' => 'My Auth0 Client'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider metadataProvider
     */
    public function it_logs_creating_an_event(Metadata $metadata, array $expectedKeys): void
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';

        $eventCreated = new EventCreated(
            $eventId,
            new Language('en'),
            'Faith no More',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('7a59de16-6111-4658-aa6e-958ff855d14e'),
            new Calendar(CalendarType::permanent()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            $metadata,
            $eventCreated,
            DateTime::fromString($now->format(DateTimeInterface::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) array_merge(
                    [
                        'date' => $now->format('c'),
                        'description' => 'Event aangemaakt in UiTdatabank',
                    ],
                    $expectedKeys
                ),
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_copying_an_event(): void
    {
        $eventId = 'f2b227c5-4756-49f6-a25d-8286b6a2351f';
        $originalEventId = '1fd05542-ce0b-4ed1-ad17-cf5a0f316da4';

        $eventCopied = new EventCopied(
            $eventId,
            $originalEventId,
            new Calendar(CalendarType::permanent())
        );

        $now = new \DateTime();

        $domainMessage = new DomainMessage(
            $eventId,
            4,
            new Metadata(['user_id' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6']),
            $eventCopied,
            DateTime::fromString($now->format(DateTimeInterface::ATOM))
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $eventId,
            [
                (object) [
                    'date' => $now->format('c'),
                    'description' => 'Event gekopieerd van ' . $originalEventId,
                    'author' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_titleTranslated(): void
    {
        $titleTranslated = new TitleTranslated(
            self::EVENT_ID_1,
            new Language('fr'),
            'Titre en français'
        );

        $translatedDate = '2015-03-26T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $titleTranslated->getItemId(),
            3,
            new Metadata(['user_id' => '3d4a9602-44ee-45c9-809e-621e2671e0c8']),
            $titleTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-26T10:17:19+02:00',
                    'description' => 'Titel vertaald (fr)',
                    'author' => '3d4a9602-44ee-45c9-809e-621e2671e0c8',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_descriptionTranslated(): void
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $descriptionTranslated,
            DateTime::fromString($translatedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Beschrijving vertaald (fr)',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_eventWasTagged(): void
    {
        $eventWasTagged = new LabelAdded(
            self::EVENT_ID_1,
            'foo'
        );

        $taggedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $eventWasTagged->getItemId(),
            2,
            new Metadata(['user_id' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6']),
            $eventWasTagged,
            DateTime::fromString($taggedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => "Label 'foo' toegepast",
                    'author' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_tagErased(): void
    {
        $tagErased = new LabelRemoved(
            self::EVENT_ID_1,
            'foo'
        );

        $tagErasedDate = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $tagErased->getItemId(),
            2,
            new Metadata(['user_id' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6']),
            $tagErased,
            DateTime::fromString($tagErasedDate)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => "Label 'foo' verwijderd",
                    'author' => 'e75fa25f-18e7-4834-bb5e-6f2acaedd3c6',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Goedgekeurd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_audience_updated(): void
    {
        $event = new AudienceUpdated(self::EVENT_ID_1, AudienceType::education());

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Toegang aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_attendanceMode_updated(): void
    {
        $event = new AttendanceModeUpdated(self::EVENT_ID_1, AttendanceMode::mixed()->toString());

        $domainMessage = new DomainMessage(
            $event->getEventId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Deelnamevorm (fysiek / online) aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_onlineUrl_updated(): void
    {
        $event = new OnlineUrlUpdated(self::EVENT_ID_1, 'https://www.publiq.be/livestream');

        $domainMessage = new DomainMessage(
            $event->getEventId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Online url aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_onlineUrl_deleted(): void
    {
        $event = new OnlineUrlDeleted(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getEventId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Online url verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Reservatie-info aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_calendar_updated(): void
    {
        $event = new CalendarUpdated(self::EVENT_ID_1, new Calendar(CalendarType::permanent()));

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Kalender-info aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Contact-info aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Beschrijving aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Event verwijderd uit UiTdatabank',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Voorzieningen aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afgekeurd als duplicaat',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afgekeurd als ongepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Geocoördinaten automatisch aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageAdded(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' toegevoegd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageRemoved(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            '0aa8d12d-26d6-409f-aa68-e8200e5c91a0',
            'description',
            'copyright holder'
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test1.jpg'),
            new Language('en')
        );

        $image2 = new Image(
            new UUID('f1926870-136c-4b06-b2a1-1fab01590847'),
            MIMEType::fromSubtype('jpeg'),
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test2.jpg'),
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' geïmporteerd uit UDB2',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' geïmporteerd uit UDB2',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test1.jpg'),
            new Language('en')
        );

        $image2 = new Image(
            new UUID('f1926870-136c-4b06-b2a1-1fab01590847'),
            MIMEType::fromSubtype('jpeg'),
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test2.jpg'),
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast via UDB2',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' aangepast via UDB2',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_video_added(): void
    {
        $event = new VideoAdded(
            self::EVENT_ID_1,
            new Video(
                '91c75325-3830-4000-b580-5778b2de4548',
                new Url('https://www.youtube.com/watch?v=123'),
                new Language('nl')
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Video \'91c75325-3830-4000-b580-5778b2de4548\' toegevoegd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_video_deleted(): void
    {
        $event = new VideoDeleted(
            self::EVENT_ID_1,
            '91c75325-3830-4000-b580-5778b2de4548'
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Video \'91c75325-3830-4000-b580-5778b2de4548\' verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_video_updated(): void
    {
        $event = new VideoUpdated(
            self::EVENT_ID_1,
            new Video(
                '91c75325-3830-4000-b580-5778b2de4548',
                new Url('https://www.youtube.com/watch?v=123'),
                new Language('nl')
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Video \'91c75325-3830-4000-b580-5778b2de4548\' aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_labels_imported(): void
    {
        $event = new LabelsImported(self::EVENT_ID_1, [], []);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Labels geïmporteerd uit JSON-LD',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => "Locatie aangepast naar '827b7d8d-8821-4870-a48b-bea9d44f557c'",
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new MediaDescription('description'),
            new CopyrightHolder('copyright holder'),
            new Url('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new MainImageSelected(self::EVENT_ID_1, $image);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Hoofdafbeelding geselecteerd: \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\'',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId('a0c6c66e-d933-4817-a335-2a5a51df1fa7'),
            new Calendar(CalendarType::permanent())
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'MajorInfo aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Organisatie \'0d7d2247-ebaa-4ff0-baf9-8ea274579cc3\' verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Organisatie \'0d7d2247-ebaa-4ff0-baf9-8ea274579cc3\' toegevoegd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
                Tariff::createBasePrice(
                    new Money(1000, new Currency('EUR'))
                ),
                new Tariffs()
            )
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Prijs-info aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            DateTimeFactory::fromAtom('2015-04-30T02:00:00+02:00')
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Gepubliceerd (publicatiedatum: \'2015-04-30T02:00:00+02:00\')',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_rejected(): void
    {
        $event = new Rejected(self::EVENT_ID_1, 'not good enough');

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => "Afgekeurd, reden: 'not good enough'",
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_available_from_updated(): void
    {
        $event = new AvailableFromUpdated(
            self::EVENT_ID_1,
            new DateTimeImmutable('2023-10-10T11:22:00+00:00')
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Publicatiedatum aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Thema aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_theme_removed(): void
    {
        $event = new ThemeRemoved(self::EVENT_ID_1);

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Thema verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_title_updated(): void
    {
        $event = new TitleUpdated(self::EVENT_ID_1, 'new title');

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Titel aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_type_updated(): void
    {
        $event = new TypeUpdated(
            self::EVENT_ID_1,
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType())
        );

        $domainMessage = new DomainMessage(
            $event->getItemId(),
            3,
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Type aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Leeftijds-info verwijderd',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
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
            new Metadata(['user_id' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7']),
            $event,
            DateTime::fromString('2015-03-27T10:17:19.176169+02:00')
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            self::EVENT_ID_1,
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'description' => 'Leeftijds-info aangepast',
                    'author' => 'fc54f5c1-aa5a-45d1-837e-919b742ca6c7',
                ],
            ]
        );
    }

    protected function assertHistoryContainsLogs(string $eventId, array $history): void
    {
        $document = $this->documentRepository->fetch($eventId);
        $body = array_values((array) $document->getBody());

        $body = array_map(function (\stdClass $log) {
            return (array) $log;
        }, $body);

        foreach ($history as $log) {
            $this->assertContains((array) $log, $body);
        }
    }
}
