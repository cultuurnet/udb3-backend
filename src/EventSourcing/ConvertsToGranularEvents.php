<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

/**
 * An interface for (usually historical) "bloated" events that contain multiple unrelated changes at once, which
 * require additional code in event listeners to handle on top of the more modern "granular" events.
 * By converting the bloated event to granular events, event listeners do not need to implement additional support for
 * the "bloated" events.
 */
interface ConvertsToGranularEvents
{
    /**
     * @return array
     *   An array of more granular events that can represent the same changes as recorded in the event that implements
     *   this interface.
     *   Useful in event listeners that already support the more granular events, so they do not need to add extra
     *   logic for more coarse events.
     */
    public function toGranularEvents(): array;
}
