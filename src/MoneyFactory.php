<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Money\Currency;
use Money\Money;

final class MoneyFactory
{
    /**
     * @param string|int|float|mixed $price
     */
    public static function create(
        $price,
        Currency $currency
    ): Money {
        self::guard($price);
        return new Money((int) round(($price*100)), $currency);
    }

    /**
     * @param string|int|float $price
     */
    public static function createFromCents(
        $price,
        Currency $currency
    ): Money {
        self::guard($price);
        return new Money((int) $price, $currency);
    }

    /**
     * @throws \InvalidArgumentException
     * @param string|int|float|mixed $value
     */
    private static function guard($value): void
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new \InvalidArgumentException(
                'Given value should be an int, string, float, double. Got ' .
                gettype($value) .
                ' instead.'
            );
        }
    }
}
