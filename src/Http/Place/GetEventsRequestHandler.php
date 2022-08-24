<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetEventsRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        $routeParameters = new RouteParameters($request);
        $placeId = $routeParameters->getPlaceId();
        $response = new JsonResponse();

        // Load all event relations from the database.
        $events = $this->eventRelationsRepository->getEventsLocatedAtPlace($placeId);

        if (!empty($events)) {
            $data = ['events' => []];

            foreach ($events as $eventId) {
                $data['events'][] = [
                    '@id' => $eventId,
                ];
            }

            $response->setData($data);
        }

        return $response;
    }
}
