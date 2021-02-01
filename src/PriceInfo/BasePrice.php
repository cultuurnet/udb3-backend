<?php

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use ValueObjects\Money\Currency;
use ValueObjects\Money\CurrencyCode;

class BasePrice implements SerializableInterface
{
    /**
     * @var Price
     */
    private $price;

    /**
     * @var string
     */
    private $currencyCodeString;

    /**
     * @param Price $price
     * @param Currency $currency
     */
    public function __construct(
        Price $price,
        Currency $currency
    ) {
        $this->price = $price;
        $this->currencyCodeString = $currency->getCode()->toNative();
    }

    /**
     * @return Price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return Currency::fromNative($this->currencyCodeString);
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'price' => $this->price->toNative(),
            'currency' => $this->currencyCodeString,
        ];
    }

    /**
     * @param array $data
     * @return BasePrice
     */
    public static function deserialize(array $data)
    {
        return new BasePrice(
            new Price($data['price']),
            Currency::fromNative($data['currency'])
        );
    }

    /**
     * @param Udb3ModelTariff $tariff
     * @return BasePrice
     */
    public static function fromUdb3ModelTariff(Udb3ModelTariff $tariff)
    {
        return new BasePrice(
            new Price($tariff->getPrice()->getAmount()),
            new Currency(CurrencyCode::fromNative($tariff->getPrice()->getCurrency()->getName()))
        );
    }
}
