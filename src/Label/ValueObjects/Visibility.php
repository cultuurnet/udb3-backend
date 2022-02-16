<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @deprecated Use new CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label
 */
class Visibility extends Enum
{
    public const VISIBLE = 'visible';
    public const INVISIBLE = 'invisible';

    public static function getAllowedValues(): array
    {
        return [
            self::VISIBLE,
            self::INVISIBLE,
        ];
    }

    public static function VISIBLE(): Visibility
    {
        return new Visibility(self::VISIBLE);
    }

    public static function INVISIBLE(): Visibility
    {
        return new Visibility(self::INVISIBLE);
    }
}
