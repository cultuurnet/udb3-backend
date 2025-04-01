<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use Money\Money;

class Tariff
{
    private TranslatedTariffName $name;

    private Money $price;

    private bool $isGroupPrice;

    public function __construct(TranslatedTariffName $name, Money $price, bool $isGroupPrice = false)
    {
        $this->name = $name;
        $this->price = $price;
        $this->isGroupPrice = $isGroupPrice;
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

    public function isGroupPrice(): bool
    {
        return $this->isGroupPrice;
    }

    public function withGroupPrice(bool $isGroupPrice): self
    {
        $c = clone $this;
        $c->isGroupPrice = $isGroupPrice;
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
