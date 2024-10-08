<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use CultuurNet\UDB3\MoneyFactory;
use Money\Currency;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PriceInfoDenormalizer implements DenormalizerInterface
{
    /**
     * @var DenormalizerInterface
     */
    private $tariffNameDenormalizer;


    public function __construct(DenormalizerInterface $tariffNameDenormalizer = null)
    {
        if (!$tariffNameDenormalizer) {
            $tariffNameDenormalizer = new TranslatedTariffNameDenormalizer();
        }

        $this->tariffNameDenormalizer = $tariffNameDenormalizer;
    }

    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("PriceInfoDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('PriceInfo data should be an array.');
        }

        $basePriceData = [];
        $tariffsData = [];

        foreach ($data as $tariffData) {
            if ($tariffData['category'] === 'base') {
                $basePriceData = $tariffData;
                continue;
            }

            if ($tariffData['category'] === 'uitpas') {
                continue;
            }

            $tariffsData[] = $tariffData;
        }

        $basePrice = $this->denormalizeTariff($basePriceData, $context);

        $tariffs = array_map(
            function ($tariffData) use ($context) {
                return $this->denormalizeTariff($tariffData, $context);
            },
            $tariffsData
        );

        return new PriceInfo($basePrice, new Tariffs(...$tariffs));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === PriceInfo::class;
    }

    /**
     * @todo Extract to a separate TariffDenormalizer
     * @throws \Money\UnknownCurrencyException
     */
    private function denormalizeTariff(array $tariffData, array $context = []): Tariff
    {
        /* @var TranslatedTariffName $tariffName */
        $tariffName = $this->tariffNameDenormalizer->denormalize(
            $tariffData['name'],
            TranslatedTariffName::class,
            null,
            $context
        );

        return new Tariff(
            $tariffName,
            MoneyFactory::create($tariffData['price'], new Currency('EUR'))
        );
    }
}
