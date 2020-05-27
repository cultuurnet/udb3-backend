<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\Event\Events\LabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved;
use CultuurNet\UDB3\LabelEventInterface;

class LabelEventIsOfEventType implements LabelEventSpecificationInterface
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
