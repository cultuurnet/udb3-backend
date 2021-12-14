<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

class TaalicoonDescription extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'Je begrijpt of spreekt nog niet veel Nederlands.',
            'Je begrijpt al een beetje Nederlands maar je spreekt het nog niet zo goed.',
            'Je begrijpt vrij veel Nederlands en kan ook iets vertellen.',
            'Je begrijpt veel Nederlands en spreekt het goed.',
        ];
    }

    public static function eenTaalicoon(): TaalicoonDescription
    {
        return new self('Je begrijpt of spreekt nog niet veel Nederlands.');
    }

    public static function tweeTaaliconen(): TaalicoonDescription
    {
        return new self('Je begrijpt al een beetje Nederlands maar je spreekt het nog niet zo goed.');
    }

    public static function drieTaaliconen(): TaalicoonDescription
    {
        return new self('Je begrijpt vrij veel Nederlands en kan ook iets vertellen.');
    }

    public static function vierTaaliconen(): TaalicoonDescription
    {
        return new self('Je begrijpt veel Nederlands en spreekt het goed.');
    }
}
