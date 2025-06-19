<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetUiTPASDetailRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private \CultureFeed_Uitpas&MockObject $uitpas;

    private GetUiTPASDetailRequestHandler $getUiTPASDetailController;

    public function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);

        $this->getUiTPASDetailController = new GetUiTPASDetailRequestHandler(
            $this->uitpas,
            new CallableIriGenerator(
                fn (string $eventId) => 'http://uitpas.mock/uitpas/events/' . $eventId
            ),
            new CallableIriGenerator(
                fn (string $eventId) => 'http://uitpas.mock/uitpas/events/' . $eventId . '/card-systems/'
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_uitpas_event_details(): void
    {
        $eventId = 'e2b91aab-b6e4-4b88-9883-8a4e653dc6e1';
        $hasTicketSales = true;

        $expected = [
            '@id' => 'http://uitpas.mock/uitpas/events/e2b91aab-b6e4-4b88-9883-8a4e653dc6e1',
            'cardSystems' => 'http://uitpas.mock/uitpas/events/e2b91aab-b6e4-4b88-9883-8a4e653dc6e1/card-systems/',
            'hasTicketSales' => $hasTicketSales,
        ];

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($eventId)
            ->willReturn($hasTicketSales);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->build('GET');

        $response = $this->getUiTPASDetailController->handle($request);

        $this->assertJsonResponse(
            new JsonResponse($expected),
            $response
        );
    }
}
