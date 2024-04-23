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

        $locationName = implode(' ', [
            $address->getStreet()->toString(),
            $address->getPostalCode()->toString(),
            $address->getLocality()->toString(),
            $address->getCountryCode()->toString(),
        ]);

        if (str_contains($title, $locationName)) {
            return '';
        }

        // the goal is to remove as much HTML as we can, if strip_tags misses a tag, we decode the html chars
        $title = htmlspecialchars_decode(strip_tags($title));

        // Decode the unicode characters
        return Json::decode('"' . $title . '"');
    }
}
