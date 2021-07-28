<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

interface ResourceOwnerRepositoryInterface
{
    /**
     * @return void
     */
    public function markResourceEditableByUser(
        StringLiteral $offerId,
        StringLiteral $uitId
    );

    public function markResourceEditableByNewUser(
        StringLiteral $offerId,
        StringLiteral $userId
    ): void;
}
