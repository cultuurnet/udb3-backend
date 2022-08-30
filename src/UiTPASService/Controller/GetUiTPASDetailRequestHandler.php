<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GetUiTPASDetailRequestHandler implements RequestHandlerInterface
{
    private CultureFeed_Uitpas $uitpas;

    private UrlGeneratorInterface $urlGenerator;

    private string $eventDetailRouteName;

    private string $eventCardSystemsRouteName;

    public function __construct(
        CultureFeed_Uitpas $uitpas,
        UrlGeneratorInterface $urlGenerator,
        string $eventDetailRouteName,
        string $eventCardSystemsRouteName
    ) {
        $this->uitpas = $uitpas;
        $this->urlGenerator = $urlGenerator;
        $this->eventDetailRouteName = $eventDetailRouteName;
        $this->eventCardSystemsRouteName = $eventCardSystemsRouteName;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $eventId = (new RouteParameters($request))->getEventId();

        $data = [
            '@id' => $this->urlGenerator->generate(
                $this->eventDetailRouteName,
                ['eventId' => $eventId],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'cardSystems' => $this->urlGenerator->generate(
                $this->eventCardSystemsRouteName,
                ['eventId' => $eventId],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'hasTicketSales' => $this->uitpas->eventHasTicketSales($eventId),
        ];

        return new JsonResponse($data);
    }
}
