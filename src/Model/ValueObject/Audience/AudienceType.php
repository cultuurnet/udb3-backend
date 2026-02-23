<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static AudienceType everyone()
 * @method static AudienceType members()
 * @method static AudienceType education()
 * @method static AudienceType childrenOnly()
 */
class AudienceType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'everyone',
            'members',
            'education',
            'childrenOnly',
        ];
    }
}
