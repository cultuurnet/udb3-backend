<?php

namespace CultuurNet\UDB3\Event;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExternalEventServiceTest extends TestCase
{
    /**
     * @var HttpClient|MockObject
     */
    protected $httpClient;

    /**
     * @var EventServiceInterface
     */
    protected $eventService;

    public function setUp()
    {
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->eventService = new ExternalEventService($this->httpClient);
    }

    /**
     * @test
     */
    public function it_should_fetch_some_external_event_and_return_it_as_a_json_encoded_string_when_asking_for_an_event()
    {
        $encodedJsonEvent = file_get_contents(__DIR__ . '/samples/event_with_udb3_place.json');
        $response = new Response(200, ['Content-Type' => 'application/json'], $encodedJsonEvent);
        $eventId = 'http://culudb-silex.dev/event/e3604613-af01-4d2b-8cee-13ab61b89651';

        $this->httpClient
            ->method('sendRequest')
            ->willReturn($response);

        $actualData = $this->eventService->getEvent($eventId);

        $this->assertEquals($encodedJsonEvent, $actualData);
    }

    /**
     * @test
     * @expectedException \CultuurNet\UDB3\Event\EventNotFoundException
     */
    public function it_should_notify_that_an_event_can_not_be_found_when_the_external_request_fails()
    {
        $response = new Response(400);
        $eventId = 'http://culudb-silex.dev/event/e3604613-af01-4d2b-8cee-13ab61b89651';

        $this->httpClient
            ->method('sendRequest')
            ->willReturn($response);

        $this->eventService->getEvent($eventId);
    }
}
