<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\MediaObject;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static MediaObjectType imageObject()
 * @method static MediaObjectType mediaObject()
 */
class MediaObjectType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'imageObject',
            'mediaObject',
        ];
    }
}
