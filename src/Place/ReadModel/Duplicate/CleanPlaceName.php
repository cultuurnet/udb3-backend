<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

class CleanPlaceName
{
    private const MAX_LENGTH_TITLE = 150;
    private const NONE = 'none';

    public static function transform(Address $address, string $title): string
    {
        if (mb_strtolower($title) === self::NONE) {
            return '';
        }

        if (mb_strlen($title) > self::MAX_LENGTH_TITLE) {
            return '';
        }

        if (str_contains($title, $address->getStreet()->toString())) {
            return '';
        }

        $title = htmlspecialchars($title);

        $title = str_replace([' BE', 'BE ', ' NL', 'NL '], [' Belgium', 'Belgium ', ' Netherlands', 'Netherlands '], $title);

        //Decode the unicode characters
        return json_decode('"' . $title . '"', true);
    }
}
