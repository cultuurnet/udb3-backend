<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;
use CultuurNet\UDB3\UiTPAS\CardSystem\DistributionKey;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetCardSystemsFromEventRequestHandlerTest extends TestCase
{
    private UiTPASClient&MockObject $uitpasClient;

    private GetCardSystemsFromEventRequestHandler $handler;

    protected function setUp(): void
    {
        $this->uitpasClient = $this->createMock(UiTPASClient::class);

        $this->handler = new GetCardSystemsFromEventRequestHandler($this->uitpasClient);
    }

    /**
     * @test
     */
    public function it_returns_the_card_systems_of_an_event_in_the_same_shape_as_the_legacy_endpoint(): void
    {
        $eventId = 'db93a8d0-331a-4575-a23d-2c78d4ceb925';

        $this->uitpasClient->expects($this->once())
            ->method('getEventCardSystems')
            ->with($eventId)
            ->willReturn([
                (new CardSystem(new Id('1'), 'Card system 1'))->withDistributionKeys([
                    new DistributionKey(new Id('1'), 'Distribution key 1'),
                    new DistributionKey(new Id('2'), 'Distribution key 2'),
                ]),
                new CardSystem(new Id('2'), 'Card system 2'),
            ]);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->build('GET');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            [
                '1' => [
                    'id' => 1,
                    'name' => 'Card system 1',
                    'distributionKeys' => [
                        '1' => ['id' => 1, 'name' => 'Distribution key 1'],
                        '2' => ['id' => 2, 'name' => 'Distribution key 2'],
                    ],
                ],
                '2' => [
                    'id' => 2,
                    'name' => 'Card system 2',
                    'distributionKeys' => [],
                ],
            ],
            Json::decodeAssociatively((string) $response->getBody())
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_json_object_when_the_event_has_no_card_systems(): void
    {
        $this->uitpasClient->expects($this->once())
            ->method('getEventCardSystems')
            ->willReturn([]);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'db93a8d0-331a-4575-a23d-2c78d4ceb925')
            ->build('GET');

        $response = $this->handler->handle($request);

        $this->assertEquals('[]', (string) $response->getBody());
    }
}
