<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\WebArchive;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

final class WebArchiveTemplate extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'tips',
            'map',
        ];
    }

    public static function tips(): WebArchiveTemplate
    {
        return new self('tips');
    }

    public static function map(): WebArchiveTemplate
    {
        return new self('map');
    }
}
