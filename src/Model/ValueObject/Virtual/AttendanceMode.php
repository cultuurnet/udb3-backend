<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Virtual;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static AttendanceMode offline()
 * @method static AttendanceMode online()
 * @method static AttendanceMode mixed()
 */
final class AttendanceMode extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'offline',
            'online',
            'mixed',
        ];
    }
}
