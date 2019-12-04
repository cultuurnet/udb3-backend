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
    const META_USER_NICK = 'Jan Janssen';
    const META_AUTH_API_KEY = 'my-super-duper-key';
    const META_API = 'json-api';
    const META_CONSUMER = 'My super duper name';
    const OCCURRED_ON = '2015-03-27T10:17:19.176169+02:00';
    const OCCURRED_ON_FORMATTED = '2015-03-27T10:17:19+02:00';
    const CDBXML_NAMESPACE = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL';

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

        $this->assertHistory(
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

        $this->assertHistory(
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
        $this->assertHistory(
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
        $this->assertHistory(
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
        $this->assertHistory(
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
        $this->assertHistory(
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

        $this->assertHistoryOfEvent(
            $placeImportedFromUDB2Event->getActorId(),
            [
                (object) [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Geïmporteerd vanuit UDB2',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
                (object) [
                    'date' => '2014-04-28T11:30:28+02:00',
                    'description' => 'Aangemaakt in UDB2',
                    'author' => 'kris.classen@overpelt.be',
                ],
            ]
        );

    }

    /**
     * @test
     */
    public function it_projects_PlaceUpdatedFromUDB2_event()
    {
        $placeImportedFromUDB2Event = $this->aPlaceIUpdatedFromUDB2Event();
        $domainMessage = $this->aDomainMessageForEvent($placeImportedFromUDB2Event->getActorId(),
            $placeImportedFromUDB2Event);

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryOfEvent(
            $placeImportedFromUDB2Event->getActorId(),
            [
                (object) [
                    'date' => self::OCCURRED_ON_FORMATTED,
                    'description' => 'Geüpdatet vanuit UDB2',
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
            ]
        );
    }

    protected function assertHistoryOfEvent(string $eventId, array $history)
    {
        /** @var JsonDocument $document */
        $document = $this->documentRepository->get($eventId);
        $this->assertEquals(
            $history,
            $document->getBody()
        );
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
            $this->getEventCdbXml(),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );
    }

    private function aPlaceIUpdatedFromUDB2Event(): PlaceUpdatedFromUDB2
    {
        return new PlaceUpdatedFromUDB2(
            'a0ee7b1c-a9c1-4da1-af7e-d15496014656',
            'xml',
            'namespace'
        );
    }

    public function assertHistory(string $eventId, string $eventDescription): void
    {
        $this->assertHistoryOfEvent(
            $eventId,
            [
                (object) [
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

    private function getEventCdbXml()
    {
        return file_get_contents(__DIR__ . '/event.xml');
    }
}
