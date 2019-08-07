<?php

namespace CultuurNet\UDB3\EventExport\CalendarSummary;

use ValueObjects\Enum\Enum;

/**
 * @method static Format LARGE()
 * @method static Format MEDIUM()
 * @method static Format SMALL()
 * @method static Format EXTRA_SMALL()
 */
class Format extends Enum
{
    const LARGE = 'lg';
    const MEDIUM = 'md';
    const SMALL = 'sm';
    const EXTRA_SMALL = 'xs';
}
