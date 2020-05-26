<?php

namespace CultuurNet\UDB3;

use Broadway\Domain\AggregateRoot;

interface LabelAwareAggregateRoot extends AggregateRoot
{
    /**
     * @param Label $label
     */
    public function addLabel(Label $label);

    /**
     * @param Label $label
     */
    public function removeLabel(Label $label);
}
