<?php

namespace CultuurNet\UDB3\UiTPASService\Controller;

use CultureFeed_Uitpas;
use CultuurNet\UDB3\UiTPASService\Controller\Response\CardSystemsJsonResponse;

class OrganizerCardSystemsController
{
    /**
     * @var CultureFeed_Uitpas
     */
    private $uitpas;

    public function __construct(CultureFeed_Uitpas $uitpas)
    {
        $this->uitpas = $uitpas;
    }

    public function get(string $organizerId): CardSystemsJsonResponse
    {
        $cardSystems = $this->uitpas->getCardSystemsForOrganizer($organizerId);
        return new CardSystemsJsonResponse($cardSystems->objects);
    }
}
