<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @deprecated Use new CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label
 */
class Privacy extends Enum
{
    public const PRIVACY_PUBLIC = 'public';
    public const PRIVACY_PRIVATE = 'private';

    public static function getAllowedValues(): array
    {
        return [
            self::PRIVACY_PUBLIC,
            self::PRIVACY_PRIVATE,
        ];
    }

    public static function PRIVACY_PUBLIC(): Privacy
    {
        return new Privacy(self::PRIVACY_PUBLIC);
    }

    public static function PRIVACY_PRIVATE(): Privacy
    {
        return new Privacy(self::PRIVACY_PRIVATE);
    }
}
