<?php

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Money;

class Tariff
{
    /**
     * @var TranslatedTariffName
     */
    private $name;

    /**
     * @var Money
     */
    private $price;


    public function __construct(TranslatedTariffName $name, Money $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * @return TranslatedTariffName
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Tariff
     */
    public function withName(TranslatedTariffName $name)
    {
        $c = clone $this;
        $c->name = $name;
        return $c;
    }

    /**
     * @return Money
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return Tariff
     */
    public function withPrice(Money $price)
    {
        $c = clone $this;
        $c->price = $price;
        return $c;
    }

    /**
     * @return Tariff
     */
    public static function createBasePrice(Money $price)
    {
        return new Tariff(
            new TranslatedTariffName(
                new Language('nl'),
                new TariffName('Basistarief')
            ),
            $price
        );
    }
}
