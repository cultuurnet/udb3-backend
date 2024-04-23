<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static AggregateType event()
 * @method static AggregateType place()
 * @method static AggregateType variation()
 * @method static AggregateType organizer()
 * @method static AggregateType media_object()
 * @method static AggregateType role()
 * @method static AggregateType label()
 * @method static AggregateType ownership()
 */
final class AggregateType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'event',
            'place',
            'variation',
            'organizer',
            'media_object',
            'role',
            'label',
            'ownership',
        ];
    }
}
