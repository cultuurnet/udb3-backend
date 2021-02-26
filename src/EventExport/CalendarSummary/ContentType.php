<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use ValueObjects\Enum\Enum;

/**
 * @method static ContentType HTML()
 * @method static ContentType PLAIN()
 */
class ContentType extends Enum
{
    public const HTML = 'text/html';
    public const PLAIN = 'text/plain';
}
