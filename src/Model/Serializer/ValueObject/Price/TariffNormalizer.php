<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class TariffNormalizer implements NormalizerInterface
{
    private bool $forBasePrice;

    public function __construct(bool $forBasePrice)
    {
        $this->forBasePrice = $forBasePrice;
    }

    /**
     * @param Tariff $tariff
     */
    public function normalize($tariff, $format = null, array $context = []): array
    {
        $data = [
            'price' => $tariff->getPrice()->getAmount(),
            'currency' => $tariff->getPrice()->getCurrency()->getName(),
        ];

        if (!$this->forBasePrice) {
            $data['name'] = (new TranslatedTariffNameNormalizer())->normalize($tariff->getName());
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Tariff::class;
    }
}
