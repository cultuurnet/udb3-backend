<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * Class Privacy
 * @package CultuurNet\UDB3\Label\ValueObjects
 * @method static Privacy public()
 * @method static Privacy private()
 */
class Privacy extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'public',
            'private',
        ];
    }
}
