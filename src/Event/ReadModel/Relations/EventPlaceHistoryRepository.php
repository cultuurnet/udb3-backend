<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface EventPlaceHistoryRepository
{
    public function storeEventPlaceStartingPoint(UUID $eventId, UUID $placeId): void;

    public function storeEventPlaceMove(UUID $eventId, UUID $oldPlaceId, UUID $newPlaceId): void;
}
