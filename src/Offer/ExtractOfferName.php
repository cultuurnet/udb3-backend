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

        if (empty($offer['mainLanguage'])) {
            $offer['mainLanguage'] = 'nl';
        }

        if (is_string($offer['name'])) {
            return $offer['name'];
        }

        return $offer['name'][$offer['mainLanguage']] ?? current($offer['name']);
    }
}
