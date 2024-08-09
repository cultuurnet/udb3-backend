<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SetCardSystemsOnEventRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    /**
     * @var CultureFeed_Uitpas&MockObject
     */
    private $uitpas;

    private SetCardSystemsOnEventRequestHandler $setCardSystemsOnEventRequestHandler;

    protected function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);

        $this->setCardSystemsOnEventRequestHandler = new SetCardSystemsOnEventRequestHandler($this->uitpas);
    }

    /**
     * @test
     */
    public function it_can_set_a_list_of_card_systems_to_an_event(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemIds = ['3', '15'];

        $this->uitpas->expects($this->once())
            ->method('setCardSystemsForEvent')
            ->with($eventId, $cardSystemIds)
            ->willReturn(null);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($cardSystemIds)
            ->build('GET');

        $response = $this->setCardSystemsOnEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_allows_an_empty_list_of_card_system_ids(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';
        $cardSystemIds = [];

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray($cardSystemIds)
            ->build('GET');

        $this->uitpas->expects($this->once())
            ->method('setCardSystemsForEvent')
            ->with($eventId, $cardSystemIds)
            ->willReturn(null);

        $response = $this->setCardSystemsOnEventRequestHandler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_throws_on_empty_body(): void
    {
        $eventId = '52943e99-51c8-4ba9-95ef-ec7d93f16ed9';

        $this->uitpas->expects($this->never())
            ->method('setCardSystemsForEvent');

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidDataWithDetail('Payload should be an array of card system ids'),
            fn () => $this->setCardSystemsOnEventRequestHandler->handle($request),
        );
    }
}
