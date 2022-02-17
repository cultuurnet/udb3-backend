<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner;

use CultuurNet\UDB3\StringLiteral;

interface ResourceOwnerRepository
{
    public function markResourceEditableByUser(
        StringLiteral $resourceId,
        StringLiteral $userId
    ): void;

    public function markResourceEditableByNewUser(
        StringLiteral $resourceId,
        StringLiteral $userId
    ): void;
}
