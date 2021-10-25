<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Money;

class Tariff
{
    private TranslatedTariffName $name;

    private Money $price;

    public function __construct(TranslatedTariffName $name, Money $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    public function getName(): TranslatedTariffName
    {
        return $this->name;
    }

    public function withName(TranslatedTariffName $name): Tariff
    {
        $c = clone $this;
        $c->name = $name;
        return $c;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function withPrice(Money $price): Tariff
    {
        $c = clone $this;
        $c->price = $price;
        return $c;
    }

    public static function createBasePrice(Money $price): Tariff
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
