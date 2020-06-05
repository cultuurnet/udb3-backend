<?php

namespace CultuurNet\UDB3\Event\Productions;

use Exception;

final class EventCannotBeAddedToProduction extends Exception
{
    public static function becauseItAlreadyBelongsToAnotherProduction(string $eventId, ProductionId $productionId)
    {
        return new self(
            'Event with id ' . $eventId . ' can not be added to production with id ' . $productionId->toNative()
        );
    }
}
