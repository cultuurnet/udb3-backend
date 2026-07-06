<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class DeleteCardSystemFromEventRequestHandler implements RequestHandlerInterface
{
    private UiTPASClient $uitpasClient;

    public function __construct(UiTPASClient $uitpasClient)
    {
        $this->uitpasClient = $uitpasClient;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();
        $cardSystemId = (int) $routeParameters->get('cardSystemId');

        $this->uitpasClient->deleteCardSystemFromEvent($eventId, $cardSystemId);

        return new Response(200);
    }
}
