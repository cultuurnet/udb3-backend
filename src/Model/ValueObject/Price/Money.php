<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class Money
{
    private int $amount;

    private Currency $currency;

    public function __construct(int $amount, Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }
}
