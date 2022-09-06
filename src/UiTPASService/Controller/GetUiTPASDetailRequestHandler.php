<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetUiTPASDetailRequestHandler implements RequestHandlerInterface
{
    private CultureFeed_Uitpas $uitpas;

    private IriGeneratorInterface $getUiTPASDetailIriGenerator;

    private IriGeneratorInterface $getCardSystemsFromEventIriGenerator;

    public function __construct(
        CultureFeed_Uitpas $uitpas,
        IriGeneratorInterface $getUiTPASDetailIriGenerator,
        IriGeneratorInterface $getCardSystemsFromEventIriGenerator
    ) {
        $this->uitpas = $uitpas;
        $this->getUiTPASDetailIriGenerator = $getUiTPASDetailIriGenerator;
        $this->getCardSystemsFromEventIriGenerator = $getCardSystemsFromEventIriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventId = (new RouteParameters($request))->getEventId();

        $data = [
            '@id' => $this->getUiTPASDetailIriGenerator->iri($eventId),
            'cardSystems' => $this->getCardSystemsFromEventIriGenerator->iri($eventId),
            'hasTicketSales' => $this->uitpas->eventHasTicketSales($eventId),
        ];

        return new JsonResponse($data);
    }
}
