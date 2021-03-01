<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Label;

use CultuurNet\UDB3\LabelAwareAggregateRoot;

interface LabelApplierInterface
{
    public function apply(LabelAwareAggregateRoot $entity);
}
