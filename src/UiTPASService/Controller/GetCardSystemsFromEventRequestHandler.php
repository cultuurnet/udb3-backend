<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use CultuurNet\UDB3\UiTPASService\Controller\Response\CardSystemsJsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetCardSystemsFromEventRequestHandler implements RequestHandlerInterface
{
    private UiTPASClient $uitpasClient;

    public function __construct(UiTPASClient $uitpasClient)
    {
        $this->uitpasClient = $uitpasClient;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventId = (new RouteParameters($request))->getEventId();

        return CardSystemsJsonResponse::fromCardSystems(
            $this->uitpasClient->getEventCardSystems($eventId)
        );
    }
}
