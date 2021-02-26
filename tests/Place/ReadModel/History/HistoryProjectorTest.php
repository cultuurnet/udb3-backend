<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Place\Events\CalendarUpdated;
use CultuurNet\UDB3\Place\Events\ContactPointUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\DescriptionUpdated;
use CultuurNet\UDB3\Place\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded;
use CultuurNet\UDB3\Place\Events\ImageRemoved;
use CultuurNet\UDB3\Place\Events\ImageUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelsImported;
use CultuurNet\UDB3\Place\Events\MainImageSelected;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\OrganizerUpdated;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Place\Events\ThemeUpdated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\Place\Events\TypeUpdated;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Place\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class HistoryProjectorTest extends TestCase
{
    private const META_USER_NICK = 'Jan Janssen';
    private const META_AUTH_API_KEY = 'my-super-duper-key';
    private const META_API = 'json-api';
    private const META_CONSUMER = 'My super duper name';
    private const OCCURRED_ON = '2015-03-27T10:17:19.176169+02:00';
    private const OCCURRED_ON_FORMATTED = '2015-03-27T10:17:19+02:00';

    /**
     * @var InMemoryDocumentRepository
     */
    private $documentRepository;

    /**
     * @var HistoryProjector
     */
    private $historyProjector;


    public function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->historyProjector = new HistoryProjector(
            $this->documentRepository
        );
    }

    /**
     * @test
     */
    public function it_projects_PlaceCreated_event()
    {
        $placeCreatedEvent = $this->aPlaceCreatedEvent();
        $domainMessage = $this->aDomainMessageForEvent($placeCreatedEvent->getPlaceId(), $placeCreatedEvent);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogWithDescription(
            $placeCreatedEvent->getPlaceId(),
            'Locatie aangemaakt in UiTdatabank'
        );
    }

    /**
     * @test
     */
    public function it_projects_PlaceDeleted_event()
    {
        $placeDeletedEvent = $this->aPlaceDeletedEvent();
        $domainMessage = $this->aDomainMessageForEvent($placeDeletedEvent->getItemId(), $placeDeletedEvent);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogWithDescription(
            $placeDeletedEvent->getItemId(),
            'Locatie verwijderd uit UiTdatabank'
        );
    }

    /**
     * @test
     */
    public function it_projects_LabelAdded_event()
    {
        $labelAddedEvent = $this->aLabelAddedEvent();
        $domainMessage = $this->aDomainMessageForEvent($labelAddedEvent->getItemId(), $labelAddedEvent);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $labelAddedEvent->getItemId(),
            "Label '{$labelAddedEvent->getLabel()}' toegepast"
        );
    }

    /**
     * @test
     */
    public function it_projects_LabelRemoved_event()
    {
        $labelRemovedEvent = $this->aLabelRemovedEvent();
        $domainMessage = $this->aDomainMessageForEvent($labelRemovedEvent->getItemId(), $labelRemovedEvent);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $labelRemovedEvent->getItemId(),
            "Label '{$labelRemovedEvent->getLabel()}' verwijderd"
        );
    }

    /**
     * @test
     */
    public function it_projects_DescriptionTranslated_event()
    {
        $descriptionTranslatedEvent = $this->aDescriptionTranslatedEvent();
        $domainMessage = $this->aDomainMessageForEvent(
            $descriptionTranslatedEvent->getItemId(),
            $descriptionTranslatedEvent
        );

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $descriptionTranslatedEvent->getItemId(),
            "Beschrijving vertaald ({$descriptionTranslatedEvent->getLanguage()})"
        );
    }

    /**
     * @test
     */
    public function it_projects_TitleTranslated_event()
    {
        $titleTranslatedEvent = $this->aTitleTranslatedEvent();
        $domainMessage = $this->aDomainMessageForEvent($titleTranslatedEvent->getItemId(), $titleTranslatedEvent);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $titleTranslatedEvent->getItemId(),
            "Titel vertaald ({$titleTranslatedEvent->getLanguage()})"
        );
    }

    /**
     * @test
     */
    public function it_projects_PlaceImportedFromUDB2_event()
    {
        $placeImportedFromUDB2Event = $this->aPlaceImportedFromUDB2Event();
        $domainMessage = $this->aDomainMessageForEvent(
            $placeImportedFromUDB2Event->getActorId(),
            $placeImportedFromUDB2Event
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $placeImportedFromUDB2Event->getActorId(),
            [
                [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Locatie geïmporteerd uit UDB2',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
                [
                    'date' => '2010-01-06T13:33:06+01:00',
                    'description' => 'Locatie aangemaakt in UDB2',
                    'author' => 'cultuurnet001',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_projects_PlaceUpdatedFromUDB2_event()
    {
        $placeImportedFromUDB2Event = $this->aPlaceUpdatedFromUDB2Event();
        $domainMessage = $this->aDomainMessageForEvent(
            $placeImportedFromUDB2Event->getActorId(),
            $placeImportedFromUDB2Event
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $placeImportedFromUDB2Event->getActorId(),
            [
                [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Locatie aangepast via UDB2',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function it_projects_AddressUpdated_event(): void
    {
        $event = $this->anAddressUpdatedEvent();
        $domainMessage = $this->aDomainMessageForEvent($event->getPlaceId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getPlaceId(),
            'Adres aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_AddressTranslated_event(): void
    {
        $event = $this->anAddressTranslatedEvent();
        $domainMessage = $this->aDomainMessageForEvent($event->getPlaceId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getPlaceId(),
            "Adres vertaald ({$event->getLanguage()->getCode()})"
        );
    }

    /**
     * @test
     */
    public function it_projects_Approved_event(): void
    {
        $event = new Approved('a0ee7b1c-a9c1-4da1-af7e-d15496014656');
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Goedgekeurd'
        );
    }

    /**
     * @test
     */
    public function it_projects_BookingInfoUpdated_event(): void
    {
        $event = new BookingInfoUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new BookingInfo());
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Reservatie-info aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_CalendarUpdated_event(): void
    {
        $event = new CalendarUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new Calendar(CalendarType::PERMANENT()));
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Kalender-info aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_ContactPointUpdated_event(): void
    {
        $event = new ContactPointUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new ContactPoint());
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Contact-info aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_DescriptionUpdated_event(): void
    {
        $event = new DescriptionUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new Description('new'));
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Beschrijving aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_FacilitiesUpdated_event(): void
    {
        $event = new FacilitiesUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', []);
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Voorzieningen aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_FlaggedAsDuplicate_event(): void
    {
        $event = new FlaggedAsDuplicate('a0ee7b1c-a9c1-4da1-af7e-d15496014656');
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Afgekeurd als duplicaat'
        );
    }

    /**
     * @test
     */
    public function it_projects_FlaggedAsInappropriate_event(): void
    {
        $event = new FlaggedAsInappropriate('a0ee7b1c-a9c1-4da1-af7e-d15496014656');
        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Afgekeurd als ongepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_GeoCoordinatesUpdated_event(): void
    {
        $event = new GeoCoordinatesUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Coordinates(
                new Latitude(0.0),
                new Longitude(0.0)
            )
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Geocoördinaten automatisch aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_ImageAdded_event(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageAdded('a0ee7b1c-a9c1-4da1-af7e-d15496014656', $image);

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' toegevoegd'
        );
    }

    /**
     * @test
     */
    public function it_projects_ImageRemoved_event(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new ImageRemoved('a0ee7b1c-a9c1-4da1-af7e-d15496014656', $image);

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' verwijderd'
        );
    }

    /**
     * @test
     */
    public function it_projects_ImageUpdated_event(): void
    {
        $event = new ImageUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder')
        );

        $domainMessage = $this->aDomainMessageForEvent((string) $event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_ImagesImportedFromUDB2_event(): void
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
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            (new ImageCollection())
                ->with($image1)
                ->with($image2)
        );

        $domainMessage = $this->aDomainMessageForEvent((string) $event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' geïmporteerd uit UDB2'
        );

        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' geïmporteerd uit UDB2'
        );
    }

    /**
     * @test
     */
    public function it_projects_ImagesUpdatedFromUDB2_event(): void
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
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            (new ImageCollection())
                ->with($image1)
                ->with($image2)
        );

        $domainMessage = $this->aDomainMessageForEvent((string) $event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Afbeelding \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\' aangepast via UDB2'
        );

        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Afbeelding \'f1926870-136c-4b06-b2a1-1fab01590847\' aangepast via UDB2'
        );
    }

    /**
     * @test
     */
    public function it_projects_LabelsImported_event(): void
    {
        $event = new LabelsImported('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new Labels());

        $domainMessage = $this->aDomainMessageForEvent((string) $event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            (string) $event->getItemId(),
            'Labels geïmporteerd uit JSON-LD'
        );
    }

    /**
     * @test
     */
    public function it_projects_MainImageSelected_event(): void
    {
        $image = new Image(
            new UUID('0aa8d12d-26d6-409f-aa68-e8200e5c91a0'),
            MIMEType::fromSubtype('jpeg'),
            new \CultuurNet\UDB3\Media\Properties\Description('description'),
            new CopyrightHolder('copyright holder'),
            Url::fromNative('https://io.uitdatabank.be/media/test.jpg'),
            new Language('en')
        );

        $event = new MainImageSelected('a0ee7b1c-a9c1-4da1-af7e-d15496014656', $image);

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Hoofdafbeelding geselecteerd: \'0aa8d12d-26d6-409f-aa68-e8200e5c91a0\''
        );
    }

    /**
     * @test
     */
    public function it_projects_MajorInfoUpdated_event(): void
    {
        $event = new MajorInfoUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Title('title'),
            new EventType('0.0.0.0', 'event type'),
            new Address(
                new Street('straat'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getPlaceId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getPlaceId(),
            'MajorInfo aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_MarkedAsCanonical_event(): void
    {
        $event = new MarkedAsCanonical(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            '6288f51f-dabe-4423-9e45-35491c5f8395'
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getPlaceId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getPlaceId(),
            'Locatie gemarkeerd als canonical'
        );
    }

    /**
     * @test
     */
    public function it_projects_MarkedAsDuplicate_event(): void
    {
        $event = new MarkedAsDuplicate(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            '6288f51f-dabe-4423-9e45-35491c5f8395'
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getPlaceId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getPlaceId(),
            'Locatie gemarkeerd als duplicaat van \'6288f51f-dabe-4423-9e45-35491c5f8395\''
        );
    }

    /**
     * @test
     */
    public function it_projects_OrganizerDeleted_event(): void
    {
        $event = new OrganizerDeleted(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            '6288f51f-dabe-4423-9e45-35491c5f8395'
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Organisatie \'6288f51f-dabe-4423-9e45-35491c5f8395\' verwijderd'
        );
    }

    /**
     * @test
     */
    public function it_projects_OrganizerUpdated_event(): void
    {
        $event = new OrganizerUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            '6288f51f-dabe-4423-9e45-35491c5f8395'
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Organisatie \'6288f51f-dabe-4423-9e45-35491c5f8395\' toegevoegd'
        );
    }

    /**
     * @test
     */
    public function it_projects_PriceInfoUpdated_event(): void
    {
        $event = new PriceInfoUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new PriceInfo(
                new BasePrice(
                    Price::fromFloat(10.0),
                    Currency::fromNative('EUR')
                )
            )
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Prijs-info aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_Published_event(): void
    {
        $event = new Published(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            DateTimeImmutable::createFromFormat(
                \DateTime::ATOM,
                '2015-04-30T02:00:00+02:00'
            )
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Gepubliceerd (publicatiedatum: \'2015-04-30T02:00:00+02:00\')'
        );
    }

    /**
     * @test
     */
    public function it_projects_Rejected_event(): void
    {
        $event = new Rejected(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new StringLiteral('not good enough')
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            "Afgekeurd, reden: 'not good enough'"
        );
    }

    /**
     * @test
     */
    public function it_projects_ThemeUpdated_event(): void
    {
        $event = new ThemeUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Theme('0.1', 'theme label')
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Thema aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_TitleUpdated_event(): void
    {
        $event = new TitleUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Title('new title')
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Titel aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_TypeUpdated_event(): void
    {
        $event = new TypeUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new EventType('0.1.1', 'type label')
        );

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Type aangepast'
        );
    }

    /**
     * @test
     */
    public function it_projects_TypicalAgeRangeDeleted_event(): void
    {
        $event = new TypicalAgeRangeDeleted('a0ee7b1c-a9c1-4da1-af7e-d15496014656');

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Leeftijds-info verwijderd'
        );
    }

    /**
     * @test
     */
    public function it_projects_TypicalAgeRangeUpdated_event(): void
    {
        $event = new TypicalAgeRangeUpdated('a0ee7b1c-a9c1-4da1-af7e-d15496014656', new AgeRange());

        $domainMessage = $this->aDomainMessageForEvent($event->getItemId(), $event);

        $this->historyProjector->handle($domainMessage);
        $this->assertHistoryContainsLogWithDescription(
            $event->getItemId(),
            'Leeftijds-info aangepast'
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

    public function aPlaceCreatedEvent(): PlaceCreated
    {
        return new PlaceCreated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Language('en'),
            new Title('Foo'),
            new EventType('1.8.2', 'PARTY!'),
            new Address(
                new Street('acmelane 12'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Calendar(
                CalendarType::PERMANENT()
            )
        );
    }

    private function aPlaceDeletedEvent(): PlaceDeleted
    {
        return new PlaceDeleted(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656'
        );
    }

    public function aMetadata(): Metadata
    {
        return new Metadata(
            [
                'user_nick' => self::META_USER_NICK,
                'auth_api_key' => self::META_AUTH_API_KEY,
                'api' => self::META_API,
                'consumer' => [
                    'name' => self::META_CONSUMER,
                ],
            ]
        );
    }

    private function aLabelAddedEvent(): LabelAdded
    {
        return new LabelAdded(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Label('Label-of-adding')
        );
    }

    private function aLabelRemovedEvent(): LabelRemoved
    {
        return new LabelRemoved(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Label('Label-of-removing')
        );
    }

    private function aDescriptionTranslatedEvent(): DescriptionTranslated
    {
        return new DescriptionTranslated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Language('en'),
            new Description('description')
        );
    }

    private function aTitleTranslatedEvent(): TitleTranslated
    {
        return new TitleTranslated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Language('en'),
            new Title('Title')
        );
    }

    private function aPlaceImportedFromUDB2Event(): PlaceImportedFromUDB2
    {
        return new PlaceImportedFromUDB2(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            $this->getActorCdbXml(),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );
    }

    private function aPlaceUpdatedFromUDB2Event(): PlaceUpdatedFromUDB2
    {
        return new PlaceUpdatedFromUDB2(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            $this->getActorCdbXml(),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );
    }

    private function anAddressUpdatedEvent(): AddressUpdated
    {
        return new AddressUpdated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Address(
                new Street('Straat 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            )
        );
    }

    private function anAddressTranslatedEvent(): AddressTranslated
    {
        return new AddressTranslated(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            new Address(
                new Street('Street 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            ),
            new Language('en')
        );
    }

    public function assertHistoryContainsLogWithDescription(
        string $eventId,
        string $eventDescription
    ): void {
        $this->assertHistoryContainsLogs(
            $eventId,
            [
                [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'author' => self::META_USER_NICK,
                    'description' => $eventDescription,
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
            ]
        );
    }

    private function aDomainMessageForEvent(string $eventId, $placeCreatedEvent): DomainMessage
    {
        return new DomainMessage(
            $eventId,
            1,
            $this->aMetadata(),
            $placeCreatedEvent,
            DateTime::fromString(self::OCCURRED_ON)
        );
    }

    private function getActorCdbXml()
    {
        return file_get_contents(__DIR__ . '/actor.xml');
    }
}
