<?php

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use ValueObjects\Money\Currency;
use ValueObjects\Money\CurrencyCode;

class Tariff implements Serializable
{
    /**
     * @var MultilingualString
     */
    private $name;

    /**
     * @var Price
     */
    private $price;

    /**
     * @var string
     */
    private $currencyCodeString;


    public function __construct(
        MultilingualString $name,
        Price $price,
        Currency $currency
    ) {
        $this->name = $name;
        $this->price = $price;
        $this->currencyCodeString = $currency->getCode()->toNative();
    }

    /**
     * @return MultilingualString
     */
    public function getName()
    {
        return $this->name;
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
            'name' => $this->name->serialize(),
            'price' => $this->price->toNative(),
            'currency' => $this->currencyCodeString,
        ];
    }

    /**
     * @return Tariff
     */
    public static function deserialize(array $data)
    {
        return new Tariff(
            MultilingualString::deserialize($data['name']),
            new Price($data['price']),
            Currency::fromNative($data['currency'])
        );
    }

    /**
     * @return Tariff
     */
    public static function fromUdb3ModelTariff(Udb3ModelTariff $udb3ModelTariff)
    {
        return new Tariff(
            MultilingualString::fromUdb3ModelTranslatedValueObject($udb3ModelTariff->getName()),
            new Price($udb3ModelTariff->getPrice()->getAmount()),
            new Currency(CurrencyCode::fromNative($udb3ModelTariff->getPrice()->getCurrency()->getName()))
        );
    }
}
