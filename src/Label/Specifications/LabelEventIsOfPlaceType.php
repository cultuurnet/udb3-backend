<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;

class LabelEventIsOfPlaceType implements LabelEventSpecificationInterface
{
    /**
     * @param LabelEventInterface $labelEvent
     * @return bool
     */
    public function isSatisfiedBy(LabelEventInterface $labelEvent)
    {
        return ($labelEvent instanceof LabelAdded || $labelEvent instanceof LabelRemoved);
    }
}
