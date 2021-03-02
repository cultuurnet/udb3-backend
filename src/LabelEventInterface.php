<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

interface LabelEventInterface
{
    /**
     * @return string
     */
    public function getItemId();

    /**
     * @return Label
     */
    public function getLabel();
}
