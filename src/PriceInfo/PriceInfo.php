<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo as Udb3ModelPriceInfo;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo instead where possible.
 */
class PriceInfo implements Serializable
{
    private BasePrice $basePrice;

    /**
     * @var Tariff[]
     */
    private array $tariffs;

    /**
     * @var Tariff[]
     */
    private array $uitpasTariffs;

    public function __construct(BasePrice $basePrice)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = [];
        $this->uitpasTariffs = [];
    }

    public function withExtraTariff(Tariff $tariff): PriceInfo
    {
        $c = clone $this;
        $c->tariffs[] = $tariff;
        return $c;
    }


    public function withExtraUiTPASTariff(Tariff $tariff): PriceInfo
    {
        $c = clone $this;
        $c->uitpasTariffs[] = $tariff;
        return $c;
    }
    public function getBasePrice(): BasePrice
    {
        return $this->basePrice;
    }

    /**
     * @return Tariff[]
     */
    public function getTariffs(): array
    {
        return $this->tariffs;
    }

    public function getUiTPASTariffs(): array
    {
        return $this->uitpasTariffs;
    }

    public function serialize(): array
    {
        $serialized = [
            'base' => $this->basePrice->serialize(),
            'tariffs' => [],
            'uitpas_tariffs' => [],
        ];

        foreach ($this->tariffs as $tariff) {
            $serialized['tariffs'][] = $tariff->serialize();
        }

        foreach ($this->uitpasTariffs as $uitpasTariff) {
            $serialized['uitpas_tariffs'][] = $uitpasTariff->serialize();
        }

        return $serialized;
    }

    public static function deserialize(array $data): PriceInfo
    {
        $basePriceInfo = BasePrice::deserialize($data['base']);

        $priceInfo = new PriceInfo($basePriceInfo);

        foreach ($data['tariffs'] as $tariffData) {
            $priceInfo = $priceInfo->withExtraTariff(
                Tariff::deserialize($tariffData)
            );
        }

        if (isset($data['uitpas_tariffs'])) {
            foreach ($data['uitpas_tariffs'] as $uitpasTariffData) {
                $priceInfo = $priceInfo->withExtraUiTPASTariff(
                    Tariff::deserialize($uitpasTariffData)
                );
            }
        }

        return $priceInfo;
    }

    public static function fromUdb3ModelPriceInfo(Udb3ModelPriceInfo $udb3ModelPriceInfo): PriceInfo
    {
        $basePrice = BasePrice::fromUdb3ModelTariff($udb3ModelPriceInfo->getBasePrice());
        $priceInfo = new PriceInfo($basePrice);

        foreach ($udb3ModelPriceInfo->getTariffs() as $udb3ModelTariff) {
            $tariff = Tariff::fromUdb3ModelTariff($udb3ModelTariff);
            $priceInfo = $priceInfo->withExtraTariff($tariff);
        }

        return $priceInfo;
    }
}
