<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use DateTimeInterface;

interface EventPlaceHistoryRepository
{
    public function storeEventPlaceStartingPoint(Uuid $eventId, Uuid $placeId, DateTimeInterface $date): void;

    public function storeEventPlaceMove(Uuid $eventId, Uuid $oldPlaceId, Uuid $newPlaceId, DateTimeInterface $date): void;
}
