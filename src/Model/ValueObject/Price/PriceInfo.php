<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class PriceInfo
{
    private Tariff $basePrice;

    private Tariffs $tariffs;

    private Tariffs $UiTPASTariffs;

    public function __construct(Tariff $basePrice, Tariffs $tariffs, Tariffs $UiTPASTariffs)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = $tariffs;
        $this->UiTPASTariffs = $UiTPASTariffs;
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

    public function getUiTPASTariffs(): Tariffs
    {
        return $this->UiTPASTariffs;
    }

    public function withTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->tariffs = $tariffs;
        return $c;
    }
}
