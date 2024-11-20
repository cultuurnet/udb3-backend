<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff as Udb3ModelTariff;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\MoneyFactory;
use Money\Currency;
use Money\Money;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Price\Tariff instead where possible.
 */
class Tariff implements Serializable
{
    private TranslatedTariffName $name;

    private Money $money;

    public function __construct(
        TranslatedTariffName $name,
        Money $money
    ) {
        $this->name = $name;
        $this->money = $money;
    }

    public function getName(): TranslatedTariffName
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
            'name' => (new TranslatedTariffNameNormalizer())->normalize($this->getName()),
            'price' => $this->getPrice()->getAmount(),
            'currency' => $this->getCurrency()->getName(),
        ];
    }

    public static function deserialize(array $data): Tariff
    {
        /** @var TranslatedTariffName $tariffName */
        $tariffName = (new TranslatedTariffNameDenormalizer())->denormalize(
            $data['name'],
            TranslatedTariffName::class
        );

        return new Tariff(
            $tariffName,
            MoneyFactory::createFromCents($data['price'], new Currency($data['currency']))
        );
    }

    public static function fromUdb3ModelTariff(Udb3ModelTariff $udb3ModelTariff): Tariff
    {
        return new Tariff(
            $udb3ModelTariff->getName(),
            $udb3ModelTariff->getPrice()
        );
    }
}
