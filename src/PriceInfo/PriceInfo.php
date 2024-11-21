<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo as Udb3ModelPriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\MoneyFactory;
use Money\Currency;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo instead where possible.
 */
class PriceInfo implements Serializable
{
    private Tariff $basePrice;

    private Tariffs $tariffs;

    private Tariffs $uitpasTariffs;

    public function __construct(Tariff $basePrice)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = new Tariffs();
        $this->uitpasTariffs = new Tariffs();
    }

    public function withExtraTariff(Tariff $tariff): PriceInfo
    {
        $c = clone $this;
        $c->tariffs = $this->tariffs->with($tariff);
        return $c;
    }

    public function withTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->tariffs = $tariffs;
        return $c;
    }

    public function withExtraUiTPASTariff(Tariff $tariff): PriceInfo
    {
        $c = clone $this;
        $c->uitpasTariffs = $this->uitpasTariffs->with($tariff);
        return $c;
    }

    public function withUiTPASTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->uitpasTariffs = $tariffs;
        return $c;
    }

    public function getBasePrice(): Tariff
    {
        return $this->basePrice;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }

    public function getUiTPASTariffs(): Tariffs
    {
        return $this->uitpasTariffs;
    }

    public function serialize(): array
    {
        $serialized = [
            'base' => [
                'price' => $this->basePrice->getPrice()->getAmount(),
                'currency' => $this->basePrice->getPrice()->getCurrency()->getName(),
            ],
            'tariffs' => [],
            'uitpas_tariffs' => [],
        ];

        $normalize = fn (Tariff $tariff): array => [
            'price' => $tariff->getPrice()->getAmount(),
            'currency' => $tariff->getPrice()->getCurrency()->getName(),
            'name' => (new TranslatedTariffNameNormalizer())->normalize($tariff->getName()),
        ];

        foreach ($this->tariffs as $tariff) {
            $serialized['tariffs'][] = $normalize($tariff);
        }

        foreach ($this->uitpasTariffs as $uitpasTariff) {
            $serialized['uitpas_tariffs'][] = $normalize($uitpasTariff);
        }

        return $serialized;
    }

    public static function deserialize(array $data): PriceInfo
    {
        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                MoneyFactory::createFromCents($data['base']['price'], new Currency($data['base']['currency']))
            )
        );

        $denormalize = function (array $tariffData): Tariff {
            /** @var TranslatedTariffName $tariffName */
            $tariffName = (new TranslatedTariffNameDenormalizer())->denormalize(
                $tariffData['name'],
                TranslatedTariffName::class
            );

            return new Tariff(
                $tariffName,
                MoneyFactory::createFromCents($tariffData['price'], new Currency($tariffData['currency']))
            );
        };

        foreach ($data['tariffs'] as $tariffData) {
            $priceInfo = $priceInfo->withExtraTariff($denormalize($tariffData));
        }

        if (isset($data['uitpas_tariffs'])) {
            foreach ($data['uitpas_tariffs'] as $uitpasTariffData) {
                $priceInfo = $priceInfo->withExtraUiTPASTariff($denormalize($uitpasTariffData));
            }
        }

        return $priceInfo;
    }

    public static function fromUdb3ModelPriceInfo(Udb3ModelPriceInfo $udb3ModelPriceInfo): PriceInfo
    {
        $priceInfo = new PriceInfo($udb3ModelPriceInfo->getBasePrice());

        foreach ($udb3ModelPriceInfo->getTariffs() as $udb3ModelTariff) {
            $priceInfo = $priceInfo->withExtraTariff($udb3ModelTariff);
        }

        return $priceInfo;
    }
}
