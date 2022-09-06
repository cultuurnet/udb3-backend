<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\Relations\InMemoryEventRelationsRepository;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

class GetEventsRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const PLACE_ID_WITH_EVENTS = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const PLACE_ID_WITHOUT_EVENTS = '16d7188a-c655-45ae-9f82-dce220ba459a';

    private const FIRST_EVENT = '88464d84-b865-4c6c-8e80-f93fa0f50c85';
    private const SECOND_EVENT = '528fa791-3c07-44fc-abdc-ea04fc97272c';
    private const THIRD_EVENT = 'b7d58e8e-516f-4201-80b4-bb2dea46351e';

    private GetEventsRequestHandler $getEventsRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $eventRelationsRepository = new InMemoryEventRelationsRepository();

        $eventRelationsRepository->storePlace(self::FIRST_EVENT, self::PLACE_ID_WITH_EVENTS);
        $eventRelationsRepository->storePlace(self::SECOND_EVENT, self::PLACE_ID_WITH_EVENTS);
        $eventRelationsRepository->storePlace(self::THIRD_EVENT, self::PLACE_ID_WITH_EVENTS);

        $this->getEventsRequestHandler = new GetEventsRequestHandler(
            $eventRelationsRepository
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_returns_the_events_of_a_place(): void
    {
        $getEventsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID_WITH_EVENTS)
            ->build('GET');

        $response = $this->getEventsRequestHandler->handle($getEventsRequest);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'events' => [
                        0 => [
                            '@id' => self::FIRST_EVENT,
                        ],
                        1 => [
                            '@id' => self::SECOND_EVENT,
                        ],
                        2 => [
                            '@id' => self::THIRD_EVENT,
                        ],
                    ],
                ]
            ),
            $response
        );
    }

    /**
     * @test
     */
    public function it_returns_no_events_if_a_place_has_none(): void
    {
        $getEventsRequest = $this->psr7RequestBuilder
            ->withRouteParameter('placeId', self::PLACE_ID_WITHOUT_EVENTS)
            ->build('GET');

        $response = $this->getEventsRequestHandler->handle($getEventsRequest);

        $this->assertJsonResponse(
            new JsonResponse(
                [
                    'events' => [],
                ]
            ),
            $response
        );
    }
}
