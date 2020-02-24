<?php

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
    const EEN_TAALICOON = "Je begrijpt of spreekt nog niet veel Nederlands.";
    const TWEE_TAALICONEN = "Je begrijpt al een beetje Nederlands maar je spreekt het nog niet zo goed.";
    const DRIE_TAALICONEN = "Je begrijpt vrij veel Nederlands en kan ook iets vertellen.";
    const VIER_TAALICONEN = "Je begrijpt veel Nederlands en spreekt het goed.";
}
