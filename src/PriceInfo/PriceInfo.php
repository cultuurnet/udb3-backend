<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TariffDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Price\TariffNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo as Udb3ModelPriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;

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
            'base' => (new TariffNormalizer(true))->normalize($this->basePrice),
            'tariffs' => [],
            'uitpas_tariffs' => [],
        ];

        $tariffNormalizer = new TariffNormalizer(false);

        foreach ($this->tariffs as $tariff) {
            $serialized['tariffs'][] = $tariffNormalizer->normalize($tariff);
        }

        foreach ($this->uitpasTariffs as $uitpasTariff) {
            $serialized['uitpas_tariffs'][] = $tariffNormalizer->normalize($uitpasTariff);
        }

        return $serialized;
    }

    public static function deserialize(array $data): PriceInfo
    {
        $priceInfo = new PriceInfo(
            (new TariffDenormalizer(true))->denormalize($data['base'], Tariff::class)
        );

        $tariffDenormalizer = new TariffDenormalizer(false);

        foreach ($data['tariffs'] as $tariffData) {
            $priceInfo = $priceInfo->withExtraTariff(
                $tariffDenormalizer->denormalize($tariffData, Tariff::class)
            );
        }

        if (isset($data['uitpas_tariffs'])) {
            foreach ($data['uitpas_tariffs'] as $uitpasTariffData) {
                $priceInfo = $priceInfo->withExtraUiTPASTariff(
                    $tariffDenormalizer->denormalize($uitpasTariffData, Tariff::class)
                );
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
