<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetEventsRequestHandler implements RequestHandlerInterface
{
    private EventRelationsRepository $eventRelationsRepository;

    public function __construct(EventRelationsRepository $eventRelationsRepository)
    {
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $placeId = $routeParameters->getPlaceId();

        $events = $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);
        $data = ['events' => []];

        if (!empty($events)) {
            foreach ($events as $eventId) {
                $data['events'][] = [
                    '@id' => $eventId,
                ];
            }
        }
        return new JsonResponse($data);
    }
}
