<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

final class KinepolisPriceParser implements PriceParser
{
    public function parseTheaterPrices(array $theaterPrices): ParsedPriceForATheater
    {
        $basePrice = 0;
        $discountPrice = 0;
        $studentPrice = 0;
        $surchargeForLongMovie = 0;
        $surchargeFor3D = 0;

        // This is done because the array of prices of Kinepolis
        // is always random and includes prices that are irrelevant for us
        // like prices for various models of 3d glasses
        // We assume the values below are always present
        foreach ($theaterPrices as $theaterPrice) {
            if ($theaterPrice[1] === 'Normaal tarief') {
                $basePrice = $this->normalizePrice($theaterPrice[0]);
            }
            if ($theaterPrice[1] === 'Kortingstarief') {
                $discountPrice = $this->normalizePrice($theaterPrice[0]);
            }
            if ($theaterPrice[1] === 'Kinepolis Student Card') {
                $studentPrice = $this->normalizePrice($theaterPrice[0]);
            }
            if ($theaterPrice[1] === 'Supplement Film Lange Speelduur (>/=2u15)') {
                $surchargeForLongMovie = $this->normalizePrice($theaterPrice[0]);
            }
            if ($theaterPrice[1] === 'Supplement 3D') {
                $surchargeFor3D = $this->normalizePrice($theaterPrice[0]);
            }
        }

        return new ParsedPriceForATheater(
            $basePrice,
            $discountPrice,
            $studentPrice,
            $surchargeForLongMovie,
            $surchargeFor3D
        );
    }

    // All their prices are in the format of € 0,00
    // The result is returned in cents. Because
    // The Money Class expects cents and not Euros
    private function normalizePrice(string $price): int
    {
        return (int) (preg_replace('/[^0-9]/', '', $price));
    }
}
