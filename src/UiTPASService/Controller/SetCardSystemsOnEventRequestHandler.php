<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SetCardSystemsOnEventRequestHandler implements RequestHandlerInterface
{
    private CultureFeed_Uitpas $uitpas;

    public function __construct(CultureFeed_Uitpas $uitpas)
    {
        $this->uitpas = $uitpas;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $content = $request->getBody()->getContents();
        $cardSystemIds = !empty($content) ? Json::decodeAssociatively($content) : null;

        if (!is_array($cardSystemIds)) {
            throw ApiProblem::bodyInvalidDataWithDetail('Payload should be an array of card system ids');
        }

        $this->uitpas->setCardSystemsForEvent(
            (new RouteParameters($request))->getEventId(),
            $cardSystemIds
        );

        return new Response(200);
    }
}
