<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetUiTPASDetailRequestHandler implements RequestHandlerInterface
{
    private UiTPASClient $uitpasClient;

    private IriGeneratorInterface $getUiTPASDetailIriGenerator;

    private IriGeneratorInterface $getCardSystemsFromEventIriGenerator;

    public function __construct(
        UiTPASClient $uitpasClient,
        IriGeneratorInterface $getUiTPASDetailIriGenerator,
        IriGeneratorInterface $getCardSystemsFromEventIriGenerator
    ) {
        $this->uitpasClient = $uitpasClient;
        $this->getUiTPASDetailIriGenerator = $getUiTPASDetailIriGenerator;
        $this->getCardSystemsFromEventIriGenerator = $getCardSystemsFromEventIriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventId = (new RouteParameters($request))->getEventId();

        $data = [
            '@id' => $this->getUiTPASDetailIriGenerator->iri($eventId),
            'cardSystems' => $this->getCardSystemsFromEventIriGenerator->iri($eventId),
            'hasTicketSales' => $this->uitpasClient->eventHasTicketSales($eventId),
        ];

        return new JsonResponse($data);
    }
}
