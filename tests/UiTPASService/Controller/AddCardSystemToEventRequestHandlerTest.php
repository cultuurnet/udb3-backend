<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultureFeed_Uitpas_Response;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddCardSystemToEventRequestHandlerTest extends TestCase
{
    /**
     * @var CultureFeed_Uitpas&MockObject
     */
    private $uitpas;

    private AddCardSystemToEventRequestHandler $addCardSystemToEventRequestHandlerTest;

    protected function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);

        $this->addCardSystemToEventRequestHandlerTest = new AddCardSystemToEventRequestHandler($this->uitpas);
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_an_automatic_distribution_key_to_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = 15;

        $this->uitpas->expects($this->once())
            ->method('addCardSystemToEvent')
            ->with($eventId, $cardSystemId)
            ->willReturn(new CultureFeed_Uitpas_Response());

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', (string) $cardSystemId)
            ->build('GET');

        $response = $this->addCardSystemToEventRequestHandlerTest->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_can_add_a_card_system_with_a_manual_distribution_key_to_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemId = '27';
        $distributionKey = '1';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withRouteParameter('cardSystemId', $cardSystemId)
            ->withRouteParameter('distributionKey', $distributionKey)
            ->build('GET');

        $response = $this->addCardSystemToEventRequestHandlerTest->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
