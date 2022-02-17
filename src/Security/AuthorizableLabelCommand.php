<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\StringLiteral;

interface AuthorizableLabelCommand
{
    /**
     * @return StringLiteral[]
     */
    public function getLabelNames(): array;
}
