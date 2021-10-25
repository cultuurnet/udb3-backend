<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class PriceInfo
{
    private Tariff $basePrice;

    private Tariffs $tariffs;

    public function __construct(Tariff $basePrice, Tariffs $tariffs)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = $tariffs;
    }

    public function getBasePrice(): Tariff
    {
        return $this->basePrice;
    }

    public function withBasePrice(Tariff $basePrice): PriceInfo
    {
        $c = clone $this;
        $c->basePrice = $basePrice;
        return $c;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }

    public function withTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->tariffs = $tariffs;
        return $c;
    }
}
