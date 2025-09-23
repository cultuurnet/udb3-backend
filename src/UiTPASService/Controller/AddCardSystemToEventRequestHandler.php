<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class AddCardSystemToEventRequestHandler implements RequestHandlerInterface
{
    private CultureFeed_Uitpas $uitpas;

    public function __construct(CultureFeed_Uitpas $uitpas)
    {
        $this->uitpas = $uitpas;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();
        $cardSystemId = $routeParameters->get('cardSystemId');
        $distributionKeyId = $routeParameters->has('distributionKeyId') ? $routeParameters->get('distributionKeyId') : null;

        $this->uitpas->addCardSystemToEvent($eventId, (int) $cardSystemId, (int) $distributionKeyId);

        return new Response(200);
    }
}
