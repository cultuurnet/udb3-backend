<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Exception;

final class EventCannotBeRemovedFromProduction extends Exception
{
    public static function becauseProductionDoesNotExist(string $eventId, ProductionId $productionId): self
    {
        return new self(
            'Event with id ' . $eventId . ' cannot be removed from production with id ' . $productionId->toNative() . ' because that production does not exist.'
        );
    }

    public static function becauseItDoesNotExist(string $eventId): self
    {
        return new self(
            'Event with id ' . $eventId . ' cannot be removed from a production because the event does not exist.'
        );
    }
}
