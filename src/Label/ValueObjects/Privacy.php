<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class Privacy extends Enum
{
    private const PUBLIC = 'public';
    private const PRIVATE = 'private';

    public static function getAllowedValues(): array
    {
        return [
            self::PUBLIC,
            self::PRIVATE,
        ];
    }

    public static function PRIVACY_PUBLIC(): Privacy
    {
        return new Privacy(self::PUBLIC);
    }

    public static function PRIVACY_PRIVATE(): Privacy
    {
        return new Privacy(self::PRIVATE);
    }
}
