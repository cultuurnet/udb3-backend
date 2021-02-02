<?php

namespace CultuurNet\UDB3\Cdb;

use ValueObjects\StringLiteral\StringLiteral;

interface CreatedByToUserIdResolverInterface
{
    /**
     * @param StringLiteral $createdByIdentifier
     * @return StringLiteral|null
     */
    public function resolveCreatedByToUserId(StringLiteral $createdByIdentifier): ?StringLiteral;
}
