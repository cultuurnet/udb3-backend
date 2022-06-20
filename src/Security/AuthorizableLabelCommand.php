<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

interface AuthorizableLabelCommand
{
    /**
     * @return string[]
     */
    public function getLabelNames(): array;
}
