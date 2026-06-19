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

    public function addCardSystemToEvent(string $eventId, int $cardSystemId, ?int $distributionKeyId = null): void;

    public function deleteCardSystemFromEvent(string $eventId, int $cardSystemId): void;

    /**
     * @param int[] $cardSystemIds
     */
    public function setCardSystemsForEvent(string $eventId, array $cardSystemIds): void;
}
