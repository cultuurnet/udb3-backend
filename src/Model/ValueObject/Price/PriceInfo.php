<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class PriceInfo
{
    private Tariff $basePrice;

    private Tariffs $tariffs;

    private Tariffs $uitpasTariffs;

    public function __construct(Tariff $basePrice, Tariffs $tariffs)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = $tariffs;
        $this->uitpasTariffs = new Tariffs();
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

    public function withUiTPASTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->uitpasTariffs = $tariffs;
        return $c;
    }

    public function getUiTPASTariffs(): Tariffs
    {
        return $this->uitpasTariffs;
    }
}
