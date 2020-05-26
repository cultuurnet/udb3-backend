<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\LabelEventInterface;

interface LabelEventSpecificationInterface
{
    /**
     * @param LabelEventInterface $labelEvent
     * @return bool
     */
    public function isSatisfiedBy(LabelEventInterface $labelEvent);
}
