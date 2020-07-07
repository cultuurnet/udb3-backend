<?php

namespace CultuurNet\UDB3\Event\Productions;

use Exception;

final class EventCannotBeAddedToProduction extends Exception
{
    public static function becauseItAlreadyBelongsToAnotherProduction(string $eventId, ProductionId $productionId)
    {
        return new self(
            'Event with id ' . $eventId . ' cannot be added to production with id ' . $productionId->toNative()
        );
    }

    public static function becauseTheyAlreadyBelongToAnotherProduction(array $eventIds, ProductionId $productionId)
    {
        return new self(
            'Events with id ' . join(',', $eventIds) . ' cannot be added to production with id ' . $productionId->toNative()
        );
    }
}
