<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteCardSystemFromEventRequestHandlerTest extends TestCase
{
    private UiTPASClient&MockObject $uitpasClient;

    private DeleteCardSystemFromEventRequestHandler $handler;

    protected function setUp(): void
    {
        $this->uitpasClient = $this->createMock(UiTPASClient::class);

        $this->handler = new DeleteCardSystemFromEventRequestHandler($this->uitpasClient);
    }

    /**
     * @test
     */
    public function it_can_remove_a_card_system_from_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '15';

        $this->uitpasClient->expects($this->once())
            ->method('deleteCardSystemFromEvent')
            ->with($eventId, 15);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', $cardSystemId)
            ->build('DELETE');

        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
