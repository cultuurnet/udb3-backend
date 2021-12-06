<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface LabelEventInterface
{
    public function getItemId(): string;

    public function getLabelName(): string;

    public function isLabelVisible(): bool;

    /**
     * @return Label
     */
    public function getLabel();
}
