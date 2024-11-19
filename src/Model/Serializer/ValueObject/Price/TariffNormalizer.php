<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class TariffNormalizer implements NormalizerInterface
{
    /**
     * @param Tariff $tariff
     */
    public function normalize($tariff, $format = null, array $context = []): array
    {
        return [
            'name' => (new TranslatedTariffNameNormalizer())->normalize($tariff->getName()),
            'price' => $tariff->getPrice()->getAmount(),
            'currency' => $tariff->getPrice()->getCurrency()->getName(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Tariff::class;
    }
}
