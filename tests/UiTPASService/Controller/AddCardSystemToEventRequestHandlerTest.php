<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddCardSystemToEventRequestHandlerTest extends TestCase
{
    private UiTPASClient&MockObject $uitpasClient;

    private AddCardSystemToEventRequestHandler $handler;

    protected function setUp(): void
    {
        $this->uitpasClient = $this->createMock(UiTPASClient::class);

        $this->handler = new AddCardSystemToEventRequestHandler($this->uitpasClient);
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_an_automatic_distribution_key_to_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '15';

        $this->uitpasClient->expects($this->once())
            ->method('addCardSystemToEvent')
            ->with($eventId, 15, null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', $cardSystemId)
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_a_manual_distribution_key_to_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '27';
        $distributionKeyId = '1';

        $this->uitpasClient->expects($this->once())
            ->method('addCardSystemToEvent')
            ->with($eventId, 27, 1);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', $cardSystemId)
            ->withRouteParameter('distributionKeyId', $distributionKeyId)
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
