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
    public const LARGE = 'lg';
    public const MEDIUM = 'md';
    public const SMALL = 'sm';
    public const EXTRA_SMALL = 'xs';
}
