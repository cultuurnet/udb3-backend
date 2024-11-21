<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PriceInfoDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): PriceInfo
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

        $tariffDenormalizer = new TariffDenormalizer();

        $basePrice = $tariffDenormalizer->denormalize($basePriceData, Tariff::class, $format, $context);

        $tariffs = array_map(
            fn ($tariffData) => $tariffDenormalizer->denormalize($tariffData, Tariff::class, $format, $context),
            $tariffsData
        );

        return new PriceInfo($basePrice, new Tariffs(...$tariffs));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === PriceInfo::class;
    }
}
