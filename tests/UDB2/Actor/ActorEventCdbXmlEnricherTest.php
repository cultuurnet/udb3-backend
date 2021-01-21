<?php

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\SimpleEventBus;
use Broadway\EventHandling\TraceableEventBus;
use CultuurNet\UDB2DomainEvents\ActorCreated;
use CultuurNet\UDB2DomainEvents\ActorUpdated;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorCreatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\Actor\Events\ActorUpdatedEnrichedWithCdbXml;
use CultuurNet\UDB3\UDB2\OfferToSapiUrlTransformer;
use CultuurNet\UDB3\UDB2\XML\XMLValidationError;
use CultuurNet\UDB3\UDB2\XML\XMLValidationException;
use CultuurNet\UDB3\UDB2\XML\XMLValidationServiceInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

class ActorEventCdbXmlEnricherTest extends TestCase
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
     * @var ActorEventCdbXmlEnricher
     */
    private $enricher;

    public function setUp()
    {
        $this->eventBus = new TraceableEventBus(
            new SimpleEventBus()
        );

        $this->eventBus->trace();

        $this->httpClient = $this->createMock(HttpClient::class);

        $this->xmlValidationService = $this->createMock(XMLValidationServiceInterface::class);

        $this->enricher = new ActorEventCdbXmlEnricher(
            $this->eventBus,
            $this->httpClient,
            $this->xmlValidationService
        );
    }

    /**
     * @dataProvider messagesProvider
     * @test
     * @param ActorUpdated|ActorCreated $incomingEvent
     * @param ActorUpdatedEnrichedWithCdbXml|ActorCreatedEnrichedWithCdbXml $newEvent
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
     * @param ActorUpdated|ActorCreated $incomingEvent
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
     * @dataProvider messagesProvider
     * @test
     * @param ActorUpdated|ActorCreated $incomingEvent
     */
    public function it_should_retrieve_cdbxml_from_sapi_with_a_transformer($incomingEvent)
    {
        $this->expectHttpClientToReturnCdbXmlFromUrl(
            'http://search-prod.lodgon.com/search/rest/detail/event/318F2ACB-F612-6F75-0037C9C29F44087A?noauth=true&version=3.3'
        );

        $transformer = new OfferToSapiUrlTransformer('http://search-prod.lodgon.com/search/rest/detail/event/%s?noauth=true&version=3.3');

        $this->enricher->withUrlTransformer($transformer);

        $this->publish($incomingEvent);
    }

    /**
     * Data provider with for each incoming message a corresponding expected new
     * message.
     */
    public function messagesProvider()
    {
        $actorCreated = $this->newActorCreated(
            new \DateTimeImmutable(
                '2013-07-18T09:04:37',
                new \DateTimeZone('Europe/Brussels')
            )
        );

        $actorUpdated = $this->newActorUpdated(
            new \DateTimeImmutable(
                '2013-07-18T09:04:37',
                new \DateTimeZone('Europe/Brussels')
            )
        );

        return [
            [
                $actorCreated,
                new ActorCreatedEnrichedWithCdbXml(
                    $actorCreated->getActorId(),
                    $actorCreated->getTime(),
                    $actorCreated->getAuthor(),
                    $actorCreated->getUrl(),
                    new StringLiteral($this->cdbXml()),
                    new StringLiteral($this->cdbXmlNamespaceUri())
                ),
            ],
            [
                $actorUpdated,
                new ActorUpdatedEnrichedWithCdbXml(
                    $actorUpdated->getActorId(),
                    $actorUpdated->getTime(),
                    $actorUpdated->getAuthor(),
                    $actorCreated->getUrl(),
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
            (string)$url,
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
        return file_get_contents(__DIR__ . '/Events/actor.xml');
    }

    private function cdbXmlNamespaceUri()
    {
        return 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';
    }

    private function publish($payload)
    {
        $this->enricher->handle(
            DomainMessage::recordNow(
                UUID::generateAsString(),
                0,
                new Metadata(),
                $payload
            )
        );
    }

    private function newActorCreated(\DateTimeImmutable $time)
    {
        $actorId = new StringLiteral('318F2ACB-F612-6F75-0037C9C29F44087A');
        $author = new StringLiteral('me@example.com');
        $url = Url::fromNative('https://io.uitdatabank.be/event/318F2ACB-F612-6F75-0037C9C29F44087A');

        return new ActorCreated(
            $actorId,
            $time,
            $author,
            $url
        );
    }

    private function newActorUpdated(\DateTimeImmutable $time)
    {
        $actorId = new StringLiteral('318F2ACB-F612-6F75-0037C9C29F44087A');
        $author = new StringLiteral('me@example.com');
        $url = Url::fromNative('https://io.uitdatabank.be/event/318F2ACB-F612-6F75-0037C9C29F44087A');

        return new ActorUpdated(
            $actorId,
            $time,
            $author,
            $url
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
}
