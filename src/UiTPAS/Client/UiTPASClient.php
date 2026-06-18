<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Client;

use CultuurNet\UDB3\UiTPAS\CardSystem\CardSystem;

interface UiTPASClient
{
    /**
     * @return CardSystem[]
     */
    public function getEventCardSystems(string $eventId): array;
}
