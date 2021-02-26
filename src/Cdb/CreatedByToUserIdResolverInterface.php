<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cdb;

use ValueObjects\StringLiteral\StringLiteral;

interface CreatedByToUserIdResolverInterface
{
    public function resolveCreatedByToUserId(StringLiteral $createdByIdentifier): ?StringLiteral;
}
