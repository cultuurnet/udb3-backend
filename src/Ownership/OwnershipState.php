<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static OwnershipState requested()
 * @method static OwnershipState claimed()
 * @method static OwnershipState rejected()
 */
final class OwnershipState extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'requested',
            'claimed',
            'rejected',
        ];
    }
}
