<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TranslatedTariffNameNormalizer;
use CultuurNet\UDB3\MoneyFactory;
use Money\Currency;

class PriceInfo
{
    private Tariff $basePrice;

    private Tariffs $tariffs;

    private Tariffs $uitpasTariffs;

    public function __construct(Tariff $basePrice, Tariffs $tariffs)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = $tariffs;
        $this->uitpasTariffs = new Tariffs();
    }

    public function getBasePrice(): Tariff
    {
        return $this->basePrice;
    }

    public function withBasePrice(Tariff $basePrice): PriceInfo
    {
        $c = clone $this;
        $c->basePrice = $basePrice;
        return $c;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }

    public function withTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->tariffs = $tariffs;
        return $c;
    }

    public function withUiTPASTariffs(Tariffs $tariffs): PriceInfo
    {
        $c = clone $this;
        $c->uitpasTariffs = $tariffs;
        return $c;
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
                'groupPrice' => $this->basePrice->isGroupPrice(),
            ],
            'tariffs' => [],
            'uitpas_tariffs' => [],
        ];

        $normalize = fn (Tariff $tariff): array => [
            'price' => $tariff->getPrice()->getAmount(),
            'currency' => $tariff->getPrice()->getCurrency()->getName(),
            'name' => (new TranslatedTariffNameNormalizer())->normalize($tariff->getName()),
            'groupPrice' => $tariff->isGroupPrice(),
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
        $denormalize = function (array $tariffData): Tariff {
            /** @var TranslatedTariffName $tariffName */
            $tariffName = (new TranslatedTariffNameDenormalizer())->denormalize(
                $tariffData['name'],
                TranslatedTariffName::class
            );

            $tariff = new Tariff(
                $tariffName,
                MoneyFactory::createFromCents($tariffData['price'], new Currency($tariffData['currency']))
            );
            return isset($tariffData['groupPrice']) ? $tariff->withGroupPrice($tariffData['groupPrice']) : $tariff;
        };

        $tarrifs = [];
        foreach ($data['tariffs'] as $tariffData) {
            $tarrifs[] = $denormalize($tariffData);
        }

        $priceInfo = new PriceInfo(
            Tariff::createBasePrice(
                MoneyFactory::createFromCents($data['base']['price'], new Currency($data['base']['currency']))
            ),
            new Tariffs(...$tarrifs)
        );

        if (isset($data['uitpas_tariffs'])) {
            $uitpasTariffs = [];
            foreach ($data['uitpas_tariffs'] as $uitpasTariffData) {
                $uitpasTariffs[] = $denormalize($uitpasTariffData);
            }
            $priceInfo = $priceInfo->withUiTPASTariffs(new Tariffs(...$uitpasTariffs));
        }

        return $priceInfo;
    }
}
