<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SetCardSystemsOnEventRequestHandler implements RequestHandlerInterface
{
    private UiTPASClient $uitpasClient;

    public function __construct(UiTPASClient $uitpasClient)
    {
        $this->uitpasClient = $uitpasClient;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $content = $request->getBody()->getContents();
        $cardSystemIds = !empty($content) ? Json::decodeAssociatively($content) : null;

        if (!is_array($cardSystemIds)) {
            throw ApiProblem::bodyInvalidDataWithDetail('Payload should be an array of card system ids');
        }

        $this->uitpasClient->setCardSystemsForEvent(
            (new RouteParameters($request))->getEventId(),
            array_map('intval', $cardSystemIds)
        );

        return new Response(200);
    }
}
