<?php

namespace CultuurNet\UDB3\Event\Productions;

use Exception;

final class EventCannotBeAddedToProduction extends Exception
{
    public static function becauseItAlreadyBelongsToAnotherProduction(string $eventId, ProductionId $productionId): self
    {
        return new self(
            'Event with id ' . $eventId . ' cannot be added to production with id ' . $productionId->toNative() . ' because it already belongs to another production.'
        );
    }

    public static function becauseSomeEventsBelongToAnotherProduction(array $eventIds, ProductionId $productionId): self
    {
        return new self(
            'Events with ids ' . implode(',', $eventIds) . ' cannot be added to production with id ' . $productionId->toNative() . ' because some events already belong to another production.'
        );
    }

    public static function becauseItDoesNotExist(string $eventId): self
    {
        return new self(
            'Event with id ' . $eventId . ' cannot be added to a production because the event does not exist.'
        );
    }
}
