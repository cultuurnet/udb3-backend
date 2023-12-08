<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

final class ExtractOfferName
{
    public static function extract(array $offer): string
    {
        if (!isset($offer['name'])) {
            return '';
        }

        return $offer['name']['nl'] ?? ($offer['name'][$offer['mainLanguage']] ?? current($offer['name']));
    }
}
