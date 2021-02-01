<?php

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventDetailController
{
    /**
     * @var CultureFeed_Uitpas
     */
    private $uitpas;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var string
     */
    private $eventDetailRouteName;

    /**
     * @var string
     */
    private $eventCardSystemsRouteName;

    /**
     * @param CultureFeed_Uitpas $uitpas
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $eventDetailRouteName
     * @param string $eventCardSystemsRouteName
     */
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

    public function get(string $eventId): JsonResponse
    {
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
