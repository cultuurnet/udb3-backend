<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static ItemType event()
 * @method static ItemType place()
 * @method static ItemType organizer()
 */
final class ItemType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'event',
            'place',
            'organizer',
        ];
    }
}
