<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\LabelEventInterface;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;

class LabelEventIsOfPlaceType implements LabelEventSpecificationInterface
{
    public function isSatisfiedBy(LabelEventInterface $labelEvent): bool
    {
        return ($labelEvent instanceof LabelAdded || $labelEvent instanceof LabelRemoved);
    }
}
