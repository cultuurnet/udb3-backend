<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use ValueObjects\Enum\Enum;

/**
 * @method static WebArchiveTemplate TIPS()
 * @method static WebArchiveTemplate MAP()
 */
final class WebArchiveTemplate extends Enum
{
    const TIPS = 'tips';
    const MAP = 'map';
}
