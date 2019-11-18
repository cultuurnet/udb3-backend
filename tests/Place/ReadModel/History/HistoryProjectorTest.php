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
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
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
        $happenedOn = '2015-03-27T10:17:19.176169+02:00';
        $placeCreatedEvent = $this->aPlaceCreatedEvent();

        $domainMessage = new DomainMessage(
            $placeCreatedEvent->getPlaceId(),
            1,
            $this->aMetadata(),
            $placeCreatedEvent,
            DateTime::fromString($happenedOn)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistory(
            $placeCreatedEvent->getPlaceId(),
            '2015-03-27T10:17:19+02:00',
            EventDescription::CREATED
        );
    }

    /**
     * @test
     */
    public function it_projects_PlaceDeleted_event()
    {
        $happenedOn = '2015-03-27T10:17:19.176169+02:00';
        $placeCreatedEvent = $this->aPlaceDeletedEvent();

        $domainMessage = new DomainMessage(
            $placeCreatedEvent->getItemId(),
            1,
            $this->aMetadata(),
            $placeCreatedEvent,
            DateTime::fromString($happenedOn)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistory(
            $placeCreatedEvent->getItemId(),
            '2015-03-27T10:17:19+02:00',
            EventDescription::DELETED
        );
    }

    /**
     * @test
     */
    public function it_projects_LabelAdded_event()
    {
        $labelAddedEvent = $this->aLabelAddedEvent();
        $happenedOn = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $labelAddedEvent->getItemId(),
            1,
            $this->aMetadata(),
            $labelAddedEvent,
            DateTime::fromString($happenedOn)
        );

        $this->historyProjector->handle($domainMessage);
        $this->assertHistory(
            $labelAddedEvent->getItemId(),
            '2015-03-27T10:17:19+02:00',
            EventDescription::LABEL_ADDED
        );
    }

    /**
     * @test
     */
    public function it_projects_LabelRemoved_event()
    {
        $labelAddedEvent = $this->aLabelRemovedEvent();
        $happenedOn = '2015-03-27T10:17:19.176169+02:00';

        $domainMessage = new DomainMessage(
            $labelAddedEvent->getItemId(),
            1,
            $this->aMetadata(),
            $labelAddedEvent,
            DateTime::fromString($happenedOn)
        );

        $this->historyProjector->handle($domainMessage);
        $this->assertHistory(
            $labelAddedEvent->getItemId(),
            '2015-03-27T10:17:19+02:00',
            EventDescription::LABEL_REMOVED
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

    private function aPlaceDeletedEvent() : PlaceDeleted
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

    public function assertHistory(string $eventId, string $dateFieldValue, string $eventDescription): void
    {
        $this->assertHistoryOfEvent(
            $eventId,
            [
                (object) [
                    'date' => $dateFieldValue,
                    'author' => self::META_USER_NICK,
                    'description' => $eventDescription,
                    'apiKey' => self::META_AUTH_API_KEY,
                    'api' => self::META_API,
                    'consumerName' => self::META_CONSUMER,
                ],
            ]
        );
    }
}
