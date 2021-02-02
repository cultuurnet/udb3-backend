<?php

namespace CultuurNet\UDB3\PriceInfo;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo as Udb3ModelPriceInfo;

class PriceInfo implements SerializableInterface
{
    /**
     * @var BasePrice
     */
    private $basePrice;

    /**
     * @var Tariff[]
     */
    private $tariffs;

    /**
     * @param BasePrice $basePrice
     */
    public function __construct(BasePrice $basePrice)
    {
        $this->basePrice = $basePrice;
        $this->tariffs = [];
    }

    /**
     * @param Tariff $tariff
     * @return PriceInfo
     */
    public function withExtraTariff(Tariff $tariff)
    {
        $c = clone $this;
        $c->tariffs[] = $tariff;
        return $c;
    }

    /**
     * @return BasePrice
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    /**
     * @return Tariff[]
     */
    public function getTariffs()
    {
        return $this->tariffs;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        $serialized = [
            'base' => $this->basePrice->serialize(),
            'tariffs' => [],
        ];

        foreach ($this->tariffs as $tariff) {
            $serialized['tariffs'][] = $tariff->serialize();
        }

        return $serialized;
    }

    /**
     * @param array $data
     * @return PriceInfo
     */
    public static function deserialize(array $data)
    {
        $basePriceInfo = BasePrice::deserialize($data['base']);

        $priceInfo = new PriceInfo($basePriceInfo);

        foreach ($data['tariffs'] as $tariffData) {
            $priceInfo = $priceInfo->withExtraTariff(
                Tariff::deserialize($tariffData)
            );
        }

        return $priceInfo;
    }

    /**
     * @param Udb3ModelPriceInfo $udb3ModelPriceInfo
     * @return PriceInfo
     */
    public static function fromUdb3ModelPriceInfo(Udb3ModelPriceInfo $udb3ModelPriceInfo)
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
