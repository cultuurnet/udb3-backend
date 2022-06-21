<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Security\AuthorizableLabelCommand;

final class RemoveLabel extends AbstractCommand implements AuthorizableLabelCommand
{
    protected string $labelName;

    public function __construct(string $itemId, string $labelName)
    {
        parent::__construct($itemId);
        $this->labelName = $labelName;
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function getLabelNames(): array
    {
        return [$this->labelName];
    }
}
