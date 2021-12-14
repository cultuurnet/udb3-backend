<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class ContentType extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'text/html',
            'text/plain',
        ];
    }

    public static function html(): ContentType
    {
        return new ContentType('text/html');
    }

    public static function plain(): ContentType
    {
        return new ContentType('text/plain');
    }
}
