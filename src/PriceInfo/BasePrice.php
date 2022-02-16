<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use Money\Currency;
use Money\Money;
use ValueObjects\Money\Currency as LegacyCurrency;
use ValueObjects\Money\CurrencyCode as LegacyCurrencyCode;

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


    public function getPrice(): float
    {
        return $this->money->getAmount() / 100;
    }

    public function getCurrency(): Currency
    {
        return $this->money->getCurrency();
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'price' => $this->getPrice(),
            'currency' => $this->getCurrency()->getName(),
        ];
    }

    /**
     * @return BasePrice
     */
    public static function deserialize(array $data)
    {
        return new BasePrice(
            new Money((int)($data['price'] * 100), new Currency($data['currency']))
        );
    }

    /**
     * @return BasePrice
     */
    public static function fromUdb3ModelTariff(Udb3ModelTariff $tariff)
    {
        return new BasePrice(
            $tariff->getPrice()
        );
    }
}
