<?php

namespace CultuurNet\UDB3\Place\Events;

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
    public function getPlaceId()
    {
        return $this->getItemId();
    }
}
