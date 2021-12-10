<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\AggregateRoot;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;

interface LabelAwareAggregateRoot extends AggregateRoot
{
    public function addLabel(Label $label): void;

    public function removeLabel(Label $label): void;
}
