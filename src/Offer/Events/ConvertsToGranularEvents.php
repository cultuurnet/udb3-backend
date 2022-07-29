<?php

namespace CultuurNet\UDB3\Offer\Events;

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
