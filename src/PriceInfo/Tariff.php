<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use Money\Currency;
use Money\Money;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Price\Tariff instead where possible.
 */
class Tariff implements Serializable
{
    private MultilingualString $name;

    private Money $money;

    public function __construct(
        MultilingualString $name,
        Money $money
    ) {
        $this->name = $name;
        $this->money = $money;
    }

    public function getName(): MultilingualString
    {
        return $this->name;
    }

    public function getPrice(): Money
    {
        return $this->money;
    }

    public function getCurrency(): Currency
    {
        return $this->money->getCurrency();
    }

    public function serialize(): array
    {
        return [
            'name' => $this->name->serialize(),
            'price' => $this->getPrice()->getAmount(),
            'currency' => $this->getCurrency()->getName(),
        ];
    }

    public static function deserialize(array $data): Tariff
    {
        return new Tariff(
            MultilingualString::deserialize($data['name']),
            new Money((int) $data['price'], new Currency($data['currency']))
        );
    }

    public static function fromUdb3ModelTariff(Udb3ModelTariff $udb3ModelTariff): Tariff
    {
        return new Tariff(
            MultilingualString::fromUdb3ModelTranslatedValueObject($udb3ModelTariff->getName()),
            $udb3ModelTariff->getPrice()
        );
    }
}
