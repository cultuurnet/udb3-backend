<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use ValueObjects\Enum\Enum;

/**
 * @method static $this EEN_TAALICOON()
 * @method static $this TWEE_TAALICONEN()
 * @method static $this DRIE_TAALICONEN()
 * @method static $this VIER_TAALICONEN()
 */
class TaalicoonDescription extends Enum
{
    public const EEN_TAALICOON = 'Je begrijpt of spreekt nog niet veel Nederlands.';
    public const TWEE_TAALICONEN = 'Je begrijpt al een beetje Nederlands maar je spreekt het nog niet zo goed.';
    public const DRIE_TAALICONEN = 'Je begrijpt vrij veel Nederlands en kan ook iets vertellen.';
    public const VIER_TAALICONEN = 'Je begrijpt veel Nederlands en spreekt het goed.';
}
