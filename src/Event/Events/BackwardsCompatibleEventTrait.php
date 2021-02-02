<?php

namespace CultuurNet\UDB3\Event\Events;

/**
 * @method string getItemId()
 */
trait BackwardsCompatibleEventTrait
{
    /**
     * @deprecated
     *   Should not be used in new code, only serves to maintain backwards-
     *   compatibility in older code.
     *
     * @return string
     */
    public function getEventId()
    {
        return $this->getItemId();
    }
}
