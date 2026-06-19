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
    public function it_logs_and_throws_on_an_unexpected_status(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $client = $this->createClient($logger);
        $this->mockHandler->append(new Response(500, [], 'boom'));

        $this->expectException(\RuntimeException::class);
        $client->getEventCardSystems('event-id-1');
    }

    /**
     * @test
     */
    public function it_enables_an_existing_card_system_and_puts_back_the_full_list(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => false],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ])),
            new Response(204, [], '')
        );

        $client->addCardSystemToEvent('event-id-1', 1);

        $request = $this->mockHandler->getLastRequest();
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals(
            'https://uitpas-test.publiq.be/events/event-id-1/card-systems',
            (string) $request->getUri()
        );
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('Bearer token-abc', $request->getHeaderLine('Authorization'));
        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => true],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ],
            Json::decodeAssociatively((string) $request->getBody())
        );
    }

    /**
     * @test
     */
    public function it_enables_a_distribution_key_on_the_card_system(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                [
                    'id' => 1,
                    'name' => 'UiTPAS Dender',
                    'enabled' => false,
                    'manualDistributionKeys' => [
                        ['id' => 123, 'name' => '3 euro per dag', 'enabled' => false],
                    ],
                ],
            ])),
            new Response(204, [], '')
        );

        $client->addCardSystemToEvent('event-id-1', 1, 123);

        $body = Json::decodeAssociatively((string) $this->mockHandler->getLastRequest()->getBody());
        $this->assertTrue($body[0]['enabled']);
        $this->assertTrue($body[0]['manualDistributionKeys'][0]['enabled']);
    }

    /**
     * @test
     */
    public function it_appends_a_card_system_that_is_not_yet_in_the_list(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([])),
            new Response(204, [], '')
        );

        $client->addCardSystemToEvent('event-id-1', 5, 99);

        $body = Json::decodeAssociatively((string) $this->mockHandler->getLastRequest()->getBody());
        $this->assertEquals(
            [
                [
                    'id' => 5,
                    'enabled' => true,
                    'manualDistributionKeys' => [['id' => 99, 'enabled' => true]],
                ],
            ],
            $body
        );
    }

    /**
     * @test
     */
    public function it_throws_when_reading_the_current_card_systems_fails(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(new Response(500, [], 'boom'));

        $this->expectException(\RuntimeException::class);
        $client->addCardSystemToEvent('event-id-1', 1);
    }

    /**
     * @test
     */
    public function it_throws_when_the_update_fails(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => false]])),
            new Response(400, [], 'invalid-card-system')
        );

        $this->expectException(\RuntimeException::class);
        $client->addCardSystemToEvent('event-id-1', 1);
    }

    /**
     * @test
     */
    public function it_disables_an_existing_card_system_and_puts_back_the_full_list(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => true],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ])),
            new Response(204, [], '')
        );

        $client->deleteCardSystemFromEvent('event-id-1', 1);

        $request = $this->mockHandler->getLastRequest();
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals(
            'https://uitpas-test.publiq.be/events/event-id-1/card-systems',
            (string) $request->getUri()
        );
        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => false],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ],
            Json::decodeAssociatively((string) $request->getBody())
        );
    }

    /**
     * @test
     */
    public function it_puts_back_the_unchanged_list_when_deleting_a_card_system_that_is_not_present(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ])),
            new Response(204, [], '')
        );

        $client->deleteCardSystemFromEvent('event-id-1', 1);

        $this->assertEquals(
            [
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ],
            Json::decodeAssociatively((string) $this->mockHandler->getLastRequest()->getBody())
        );
    }

    /**
     * @test
     */
    public function it_enables_the_given_card_systems_and_disables_the_rest(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => true],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => false],
            ])),
            new Response(204, [], '')
        );

        $client->setCardSystemsForEvent('event-id-1', [8]);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => false],
                ['id' => 8, 'name' => 'UiTPAS Gent', 'enabled' => true],
            ],
            Json::decodeAssociatively((string) $this->mockHandler->getLastRequest()->getBody())
        );
    }

    /**
     * @test
     */
    public function it_appends_card_systems_that_are_not_yet_in_the_list_when_setting(): void
    {
        $client = $this->createClient(new NullLogger());
        $this->mockHandler->append(
            new Response(200, [], Json::encode([
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => true],
            ])),
            new Response(204, [], '')
        );

        $client->setCardSystemsForEvent('event-id-1', [5]);

        $this->assertEquals(
            [
                ['id' => 1, 'name' => 'UiTPAS Dender', 'enabled' => false],
                ['id' => 5, 'enabled' => true],
            ],
            Json::decodeAssociatively((string) $this->mockHandler->getLastRequest()->getBody())
        );
    }
}
