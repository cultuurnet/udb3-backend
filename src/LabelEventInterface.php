<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface LabelEventInterface
{
    public function getItemId(): string;

    /**
     * @return Label
     */
    public function getLabel();
}
