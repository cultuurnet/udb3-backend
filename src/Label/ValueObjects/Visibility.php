<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class Visibility extends Enum
{
    private const VISIBLE = 'visible';
    private const INVISIBLE = 'invisible';

    public static function getAllowedValues(): array
    {
        return [
            self::VISIBLE,
            self::INVISIBLE,
        ];
    }

    public static function visible(): Visibility
    {
        return new Visibility(self::VISIBLE);
    }

    public static function invisible(): Visibility
    {
        return new Visibility(self::INVISIBLE);
    }
}
