<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * Class EventAdvantage
 *
 * @package CultuurNet\UDB3\EventExport\Format\HTML\Uitpas
 *
 * @method static EventAdvantage POINT_COLLECTING()
 * @method static EventAdvantage KANSENTARIEF()
 */
class EventAdvantage extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'POINT_COLLECTING',
            'KANSENTARIEF',
        ];
    }
}
