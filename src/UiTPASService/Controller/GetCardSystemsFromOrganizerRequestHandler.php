<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\UiTPASService\Controller\Response\CardSystemsJsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCardSystemsFromOrganizerRequestHandler implements RequestHandlerInterface
{
    private CultureFeed_Uitpas $uitpas;

    public function __construct(CultureFeed_Uitpas $uitpas)
    {
        $this->uitpas = $uitpas;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventId = (new RouteParameters($request))->getOrganizerId();

        $cardSystems = $this->uitpas->getCardSystemsForOrganizer($eventId);
        return new CardSystemsJsonResponse($cardSystems->objects);
    }
}
