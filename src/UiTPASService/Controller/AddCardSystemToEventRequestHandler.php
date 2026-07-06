<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AddCardSystemToEventRequestHandler implements RequestHandlerInterface
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
        $distributionKeyId = $routeParameters->has('distributionKeyId')
            ? (int) $routeParameters->get('distributionKeyId')
            : null;

        $this->uitpasClient->addCardSystemToEvent($eventId, $cardSystemId, $distributionKeyId);

        return new Response(200);
    }
}
