<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static Format lg()
 * @method static Format md()
 * @method static Format sm()
 * @method static Format xs()
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
