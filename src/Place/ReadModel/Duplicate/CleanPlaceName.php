<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;

class CleanPlaceName
{
    private const MAX_LENGTH_TITLE = 150;

    public static function transform(Address $address, string $title): string
    {
        if (mb_strlen($title) > self::MAX_LENGTH_TITLE) {
            return '';
        }

        if (str_contains($title, $address->getStreet()->toString())) {
            return '';
        }

        // the goal is to remove as much HTML as we can, if strip_tags misses a tag, we decode the html chars
        $title = htmlspecialchars_decode(strip_tags($title));

        $title = self::replaceCountryCodes($title);

        // Decode the unicode characters
        return Json::decode('"' . $title . '"');
    }

    private static function replaceCountryCodes(string $title): string
    {
        $countries = [
            // All EU countries, probably overkill - thank you chat GPT
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'BG' => 'Bulgaria',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DE' => 'Germany',
            'DK' => 'Denmark',
            'EE' => 'Estonia',
            'ES' => 'Spain',
            'FI' => 'Finland',
            'FR' => 'France',
            'GR' => 'Greece',
            'HR' => 'Croatia',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
            'IT' => 'Italy',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'LV' => 'Latvia',
            'MT' => 'Malta',
            'NL' => 'Netherlands',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'RO' => 'Romania',
            'SE' => 'Sweden',
            'SI' => 'Slovenia',
            'SK' => 'Slovakia',
        ];

        foreach ($countries as $countryCode => $countryName) {
            if ($title === $countryCode || $title === $countryName) {
                // Of the full title is just the country or language code, it is double information -> we remove it.
                return '';
            }

            // Check space before or after to make sure we are not changing BE in the middle of a word
            $title = str_replace(["$countryCode ", " $countryCode"], ["$countryName ", " $countryName"], $title);
        }

        return $title;
    }
}
