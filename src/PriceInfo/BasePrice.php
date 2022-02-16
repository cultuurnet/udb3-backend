<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use Money\Currency;
use Money\Money;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Price\Tariff instead where possible.
 */
class BasePrice implements Serializable
{
    private Money $money;

    public function __construct(
        Money $money
    ) {
        $this->money = $money;
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
            'price' => $this->getPrice()->getAmount(),
            'currency' => $this->getCurrency()->getName(),
        ];
    }

    public static function deserialize(array $data): BasePrice
    {
        return new BasePrice(
            new Money((int) $data['price'], new Currency($data['currency']))
        );
    }

    public static function fromUdb3ModelTariff(Udb3ModelTariff $tariff): BasePrice
    {
        return new BasePrice(
            $tariff->getPrice()
        );
    }
}
