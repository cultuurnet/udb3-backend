<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Security\AuthorizableLabelCommand;

abstract class AbstractLabelCommand extends AbstractCommand implements AuthorizableLabelCommand
{
    protected Label $label;

    public function __construct(string $itemId, Label $label)
    {
        parent::__construct($itemId);
        $this->label = $label;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function getLabelNames(): array
    {
        return [$this->label->getName()->toNative()];
    }
}
