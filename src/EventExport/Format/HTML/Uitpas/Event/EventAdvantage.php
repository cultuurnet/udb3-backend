<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class EventAdvantage extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'POINT_COLLECTING',
            'KANSENTARIEF',
        ];
    }

    public static function pointCollecting(): EventAdvantage
    {
        return new EventAdvantage('POINT_COLLECTING');
    }

    public static function kansenTarief(): EventAdvantage
    {
        return new EventAdvantage('KANSENTARIEF');
    }
}
