<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Event;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventHandling\TraceableEventBus;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\UDB2\DomainEvents\EventCreated;
use CultuurNet\UDB3\UDB2\DomainEvents\EventUpdated;
use CultuurNet\UDB3\UDB2\Event\Events\EventCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Event\Events\EventUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\XML\XMLValidationError;
use CultuurNet\UDB3\UDB2\XML\XMLValidationException;
use CultuurNet\UDB3\UDB2\XML\XMLValidationServiceInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidFactory;
use CultuurNet\UDB3\StringLiteral;

class EventCdbXmlEnricherTest extends TestCase
{
    /**
     * @var TraceableEventBus
     */
    private $eventBus;

    /**
     * @var HttpClient|MockObject
     */
    private $httpClient;

    /**
     * @var XMLValidationServiceInterface|MockObject
     */
    private $xmlValidationService;

    /**
     * @var EventCdbXmlEnricher
     */
    private $enricher;

    public function setUp()
    {
        $this->eventBus = new TraceableEventBus(new SimpleEventBus());
        $this->eventBus->trace();

        $this->httpClient = $this->createMock(HttpClient::class);

        $this->xmlValidationService = $this->createMock(XMLValidationServiceInterface::class);

        $this->enricher = new EventCdbXmlEnricher(
            $this->eventBus,
            $this->httpClient,
            new UuidFactory(),
            $this->xmlValidationService
        );
    }

    /**
     * @dataProvider messagesProvider
     * @test
     * @param EventUpdated|EventCreated $incomingEvent
     * @param EventUpdatedEnrichedWithCdbXml|EventCreatedEnrichedWithCdbXml $newEvent
     */
    public function it_publishes_a_new_message_enriched_with_xml(
        $incomingEvent,
        $newEvent
    ) {
        $this->expectHttpClientToReturnCdbXmlFromUrl(
            $incomingEvent->getUrl()
        );

        $this->expectCdbXmlToBeValid($this->cdbXml());

        $this->publish($incomingEvent);

        $this->assertTracedEvents(
            [
                $newEvent,
            ]
        );
    }

    /**
     * @dataProvider messagesProvider
     * @test
     * @param EventUpdated|EventCreated $incomingEvent
     */
    public function it_throws_an_exception_when_the_imported_cdbxml_is_invalid(
        $incomingEvent
    ) {
        $this->expectHttpClientToReturnCdbXmlFromUrl(
            $incomingEvent->getUrl()
        );

        $errors = [new XMLValidationError('Oops', 0, 0)];

        $this->expectCdbXmlToBeInvalid(
            $this->cdbXml(),
            $errors
        );

        $this->expectException(XMLValidationException::class);
        $this->expectExceptionMessage('Oops (Line: 0, column: 0)');

        $this->publish($incomingEvent);
    }

    /**
     * Data provider with for each incoming message a corresponding expected new
     * message.
     */
    public function messagesProvider()
    {
        $eventCreated = $this->newEventCreated(
            new \DateTimeImmutable(
                '2013-07-18T09:04:37',
                new \DateTimeZone('Europe/Brussels')
            )
        );

        $eventUpdated = $this->newEventUpdated(
            new \DateTimeImmutable(
                '2013-07-18T09:04:37',
                new \DateTimeZone('Europe/Brussels')
            )
        );

        return [
            [
                $eventCreated,
                new EventCreatedEnrichedWithCdbXml(
                    $eventCreated->getEventId(),
                    $eventCreated->getTime(),
                    $eventCreated->getAuthor(),
                    $eventCreated->getUrl(),
                    new StringLiteral($this->cdbXml()),
                    new StringLiteral($this->cdbXmlNamespaceUri())
                ),
            ],
            [
                $eventUpdated,
                new EventUpdatedEnrichedWithCdbXml(
                    $eventUpdated->getEventId(),
                    $eventUpdated->getTime(),
                    $eventUpdated->getAuthor(),
                    $eventCreated->getUrl(),
                    new StringLiteral($this->cdbXml()),
                    new StringLiteral($this->cdbXmlNamespaceUri())
                ),
            ],
        ];
    }

    private function expectHttpClientToReturnCdbXmlFromUrl($url)
    {
        $request = new Request(
            'GET',
            $url->toString(),
            [
                'Accept' => 'application/xml',
            ]
        );

        $response = new Response(
            200,
            [],
            $this->cdbXml()
        );

        $this->httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($response);
    }

    /**
     * @param string $cdbxml
     */
    private function expectCdbXmlToBeValid($cdbxml)
    {
        $this->xmlValidationService->expects($this->once())
            ->method('validate')
            ->with($cdbxml)
            ->willReturn([]);
    }

    /**
     * @param string $cdbxml
     * @param XMLValidationError[] $errors
     */
    private function expectCdbXmlToBeInvalid($cdbxml, array $errors)
    {
        $this->xmlValidationService->expects($this->once())
            ->method('validate')
            ->with($cdbxml)
            ->willReturn($errors);
    }

    private function cdbXml()
    {
        return file_get_contents(__DIR__ . '/samples/event.xml');
    }

    private function cdbXmlNamespaceUri()
    {
        return 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';
    }

    private function publish($payload)
    {
        $this->enricher->handle(
            DomainMessage::recordNow(
                'b868ba1d-13f2-4c5a-9b4c-4fb80730d2b0',
                0,
                new Metadata(),
                $payload
            )
        );
    }

    /**
     * @param object[] $expectedEvents
     */
    protected function assertTracedEvents($expectedEvents)
    {
        $events = $this->eventBus->getEvents();

        $this->assertEquals(
            $expectedEvents,
            $events
        );
    }

    /**
     * @return EventCreated
     */
    private function newEventCreated(\DateTimeImmutable $time)
    {
        $eventId = new StringLiteral('d53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1');
        $author = new StringLiteral('jonas@cultuurnet.be');
        $url = new Url('https://io.uitdatabank.be/event/d53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1');

        return new EventCreated(
            $eventId,
            $time,
            $author,
            $url
        );
    }

    /**
     * @return EventUpdated
     */
    private function newEventUpdated(\DateTimeImmutable $time)
    {
        $eventId = new StringLiteral('d53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1');
        $author = new StringLiteral('jonas@cultuurnet.be');
        $url = new Url('https://io.uitdatabank.be/event/d53c2bc9-8f0e-4c9a-8457-77e8b3cab3d1');

        return new EventUpdated(
            $eventId,
            $time,
            $author,
            $url
        );
    }
}
