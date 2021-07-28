<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

interface ResourceOwnerRepositoryInterface
{
    public function markResourceEditableByUser(
        StringLiteral $offerId,
        StringLiteral $userId
    ): void;

    public function markResourceEditableByNewUser(
        StringLiteral $offerId,
        StringLiteral $userId
    ): void;
}
