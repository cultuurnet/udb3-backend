<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GetUiTPASDetailRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const EVENT_DETAIL = 'mock.event.detail';
    private const EVENT_CARD_SYSTEMS = 'mock.event.card_systems';

    /**
     * @var CultureFeed_Uitpas|MockObject
     */
    private $uitpas;

    /**
     * @var UrlGeneratorInterface|MockObject
     */
    private $urlGenerator;

    private GetUiTPASDetailRequestHandler $getUiTPASDetailController;

    public function setUp(): void
    {
        $this->uitpas = $this->createMock(CultureFeed_Uitpas::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->getUiTPASDetailController = new GetUiTPASDetailRequestHandler(
            $this->uitpas,
            $this->urlGenerator,
            self::EVENT_DETAIL,
            self::EVENT_CARD_SYSTEMS
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
            '@id' => 'http://uitpas.mock/events/e2b91aab-b6e4-4b88-9883-8a4e653dc6e1',
            'cardSystems' => 'http://uitpas.mock/events/e2b91aab-b6e4-4b88-9883-8a4e653dc6e1/cardSystems/',
            'hasTicketSales' => $hasTicketSales,
        ];

        $this->uitpas->expects($this->once())
            ->method('eventHasTicketSales')
            ->with($eventId)
            ->willReturn($hasTicketSales);

        $this->urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturnCallback(
                function ($routeName, $parameters, $referenceType) {
                    if (!isset($parameters['eventId'])) {
                        throw new \InvalidArgumentException('Expected eventId parameter.');
                    }

                    $url = '';
                    switch ($routeName) {
                        case self::EVENT_DETAIL:
                            $url = '/events/' . $parameters['eventId'];
                            break;
                        case self::EVENT_CARD_SYSTEMS:
                            $url = '/events/' . $parameters['eventId'] . '/cardSystems/';
                            break;
                    }

                    if ($referenceType === UrlGeneratorInterface::ABSOLUTE_URL) {
                        $url = 'http://uitpas.mock' . $url;
                    }

                    return $url;
                }
            );

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
