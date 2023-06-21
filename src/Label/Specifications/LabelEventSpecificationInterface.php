<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\LabelEventInterface;

interface LabelEventSpecificationInterface
{
    public function isSatisfiedBy(LabelEventInterface $labelEvent): bool;
}
