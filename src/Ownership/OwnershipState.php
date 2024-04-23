<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static OwnershipState requested()
 * @method static OwnershipState approved()
 * @method static OwnershipState rejected()
 * @method static OwnershipState deleted()
 */
final class OwnershipState extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'requested',
            'approved',
            'rejected',
            'deleted',
        ];
    }
}
