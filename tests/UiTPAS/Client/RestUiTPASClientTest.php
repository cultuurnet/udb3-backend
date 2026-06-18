<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Client;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class RestUiTPASClientTest extends TestCase
{
    private MockHandler $mockHandler;

    private function createClient(LoggerInterface $logger): RestUiTPASClient
    {
        $this->mockHandler = new MockHandler();
        $httpClient = new Client(['handler' => HandlerStack::create($this->mockHandler)]);

        $tokenProvider = $this->createMock(ManagementTokenProvider::class);
        $tokenProvider->method('token')->willReturn('token-abc');

        return new RestUiTPASClient($httpClient, $tokenProvider, 'https://uitpas-test.publiq.be/', $logger);
    }

    /**
     * @test
     */
    public function it_maps_event_card_systems_and_sends_a_bearer_token(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => true],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ]))
        );

        $cardSystems = $client->getEventCardSystems('event-id-1');

        $this->assertCount(2, $cardSystems);
        $this->assertEquals('1', $cardSystems[0]->getId()->toNative());
        $this->assertEquals('UiTPAS Dender', $cardSystems[0]->getName());
        $this->assertEquals('8', $cardSystems[1]->getId()->toNative());

        $request = $this->mockHandler->getLastRequest();
        $this->assertEquals(
            'https://uitpas-test.publiq.be/events/event-id-1/card-systems',
            (string) $request->getUri()
        );
        $this->assertEquals('Bearer token-abc', $request->getHeaderLine('Authorization'));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_on_404(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(new Response(404, [], ''));

        $this->assertEquals([], $client->getEventCardSystems('unknown-event'));
    }

    /**
     * @test
     */
    public function it_logs_and_returns_an_empty_list_on_an_unexpected_status(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $client = $this->createClient($logger);
        $this->mockHandler->append(new Response(500, [], 'boom'));

        $this->assertEquals([], $client->getEventCardSystems('event-id-1'));
    }
}
