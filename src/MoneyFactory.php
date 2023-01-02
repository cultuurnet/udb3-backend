<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Money\Currency;
use Money\Money;

final class MoneyFactory
{
    /**
     * @param string|int|float $price
     */
    public static function create(
        $price,
        Currency $currency
    ): Money {
        return new Money((int) round(($price*100)), $currency);
    }

    /**
     * @param string|int $price
     */
    public static function createFromCentsValue(
        $price,
        Currency $currency
    ): Money {
        return new Money((int) $price, $currency);
    }
}
