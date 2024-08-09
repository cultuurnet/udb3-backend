<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteCardSystemFromEventRequestHandlerTest extends TestCase
{
    /**
     * @var CultureFeed_Uitpas&MockObject
     */
    private $uitpas;

    private DeleteCardSystemFromEventRequestHandler $deleteCardSystemFromEventRequestHandler;

    protected function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);

        $this->deleteCardSystemFromEventRequestHandler = new DeleteCardSystemFromEventRequestHandler($this->uitpas);
    }

    /**
     * @test
     */
    public function it_can_remove_a_card_system_from_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '15';

        $this->uitpas->expects($this->once())
            ->method('deleteCardSystemFromEvent')
            ->with($eventId, $cardSystemId)
            ->willReturn(null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', $cardSystemId)
            ->build('GET');

        $response = $this->deleteCardSystemFromEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
