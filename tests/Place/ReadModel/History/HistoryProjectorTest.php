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
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\ReadModel\Enum\EventDescription;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class HistoryProjectorTest extends TestCase
{

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
    public function it_handles_PlaceCreated_event()
    {
        $happenedOn = '2015-03-27T10:17:19.176169+02:00';
        $userNick = 'Jan Janssen';
        $authApiKey = 'my-super-duper-key';
        $api = 'json-api';
        $consumer = 'My super duper name';

        $placeCreatedEvent = $this->aPlaceCreatedEvent();

        $domainMessage = new DomainMessage(
            $placeCreatedEvent->getPlaceId(),
            1,
            $this->aMetadata($userNick, $authApiKey, $api, $consumer),
            $placeCreatedEvent,
            DateTime::fromString($happenedOn)
        );

        $this->historyProjector->handle($domainMessage);

        $this->assertHistoryOfEvent(
            $placeCreatedEvent->getPlaceId(),
            [
                (object) [
                    'date' => '2015-03-27T10:17:19+02:00',
                    'author' => $userNick,
                    'description' => EventDescription::CREATED,
                    'apiKey' => $authApiKey,
                    'api' => $api,
                    'consumerName' => $consumer,
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

    public function aMetadata(string $userNick, string $authApiKey, string $api, string $consumer): Metadata
    {
        return new Metadata(
            [
                'user_nick' => $userNick,
                'auth_api_key' => $authApiKey,
                'api' => $api,
                'consumer' => [
                    'name' => $consumer,
                ]
            ]
        );
    }

}
