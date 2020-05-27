<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;

class LabelEventIsOfOrganizerType implements LabelEventSpecificationInterface
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
