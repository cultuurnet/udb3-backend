<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\History;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\DescriptionTranslated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\ReadModel\Enum\EventDescription;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use CultuurNet\UDB3\Label;

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
            'Aangemaakt in UiTdatabank'
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
            'Place verwijderd'
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
        $domainMessage = $this->aDomainMessageForEvent($descriptionTranslatedEvent->getItemId(),
            $descriptionTranslatedEvent);

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
        $domainMessage = $this->aDomainMessageForEvent($placeImportedFromUDB2Event->getActorId(),
            $placeImportedFromUDB2Event);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $placeImportedFromUDB2Event->getActorId(),
            [
                [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Geïmporteerd vanuit UDB2',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
                [
                    'date' => '2010-01-06T13:33:06+01:00',
                    'description' => 'Aangemaakt in UDB2',
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
        $domainMessage = $this->aDomainMessageForEvent($placeImportedFromUDB2Event->getActorId(),
            $placeImportedFromUDB2Event);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryContainsLogs(
            $placeImportedFromUDB2Event->getActorId(),
            [
                [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Geüpdatet vanuit UDB2',
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
                ]
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
