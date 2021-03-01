<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission;

use ValueObjects\StringLiteral\StringLiteral;

interface PermissionRepositoryInterface
{
    /**
     * @return void
     */
    public function markOfferEditableByUser(
        StringLiteral $offerId,
        StringLiteral $uitId
    );

    public function markOfferEditableByNewUser(
        StringLiteral $offerId,
        StringLiteral $userId
    ): void;
}
