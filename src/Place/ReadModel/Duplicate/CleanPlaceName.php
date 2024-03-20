<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Json;
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

        // the goal is to remove as much HTML as we can, if strip_tags misses a tag, we decode the html chars
        $title = htmlspecialchars_decode(strip_tags($title));

        $title = str_replace([' BE', 'BE ', ' NL', 'NL '], [' Belgium', 'Belgium ', ' Netherlands', 'Netherlands '], $title);

        // A specific hack to fix a location, because it gives errors in the google maps api
        // Based on the Ai Python script
        $title = str_replace('Tennisclub%20Kouterslag', 'Tennisclub_Kouterslag', $title);

        // Decode the unicode characters
        return Json::decode('"' . $title . '"');
    }
}
