<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use CultuurNet\UDB3\StringLiteral;

interface CreatedByToUserIdResolverInterface
{
    public function resolveCreatedByToUserId(StringLiteral $createdByIdentifier): ?StringLiteral;
}
