<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Money\Currency;
use Money\Money;

final class MoneyFactory
{
    public static function createFromFloat(
        float $price,
        Currency $currency
    ): Money {
        return new Money((int) round(($price*100)), $currency);
    }
}
