<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static Format LARGE()
 * @method static Format MEDIUM()
 * @method static Format SMALL()
 * @method static Format EXTRA_SMALL()
 */
class Format extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'lg',
            'md',
            'sm',
            'xs',
        ];
    }
}
