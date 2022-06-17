<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\StringLiteral;

final class RemoveLabel extends AbstractCommand
{
    protected string $labelName;

    protected bool $isVisible;

    public function __construct(string $itemId, string $labelName, bool $isVisible = true)
    {
        parent::__construct($itemId);
        $this->labelName = $labelName;
        $this->isVisible = $isVisible;
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function getLabelNames(): array
    {
        return [
            new StringLiteral($this->labelName),
        ];
    }
}
