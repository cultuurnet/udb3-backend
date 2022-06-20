<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

final class RemoveLabel extends AbstractCommand
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

    public function isVisible(): bool
    {
        return true;
    }
}
