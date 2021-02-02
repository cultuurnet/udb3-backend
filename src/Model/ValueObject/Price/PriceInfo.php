<?php

namespace CultuurNet\UDB3\Model\ValueObject\Price;

class PriceInfo
{
    /**
     * @var Tariff
     */
    private $basePrice;

    /**
     * @var Tariffs
     */
    private $tariffs;

    /**
     * @param Tariff $basePrice
     * @param Tariffs $tariffs
     */
    public function __construct(Tariff $basePrice, Tariffs $tariffs)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = $tariffs;
    }

    /**
     * @return Tariff
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * @param Tariff $basePrice
     * @return PriceInfo
     */
    public function withBasePrice(Tariff $basePrice)
    {
        $c = clone $this;
        $c->basePrice = $basePrice;
        return $c;
    }

    /**
     * @return Tariffs
     */
    public function getTariffs()
    {
        return $this->tariffs;
    }

    /**
     * @param Tariffs $tariffs
     * @return PriceInfo
     */
    public function withTariffs(Tariffs $tariffs)
    {
        $c = clone $this;
        $c->tariffs = $tariffs;
        return $c;
    }
}
