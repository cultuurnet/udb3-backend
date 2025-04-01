<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariff;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TariffNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof Tariff) {
            throw new InvalidArgumentException(sprintf('Invalid object type, expected %s, received %s.', Tariff::class, get_class($object)));
        }

        return [
            'price' => (new MoneyNormalizer())->normalize($object->getPrice()),
            'names' => $this->getNames($object),
            'groupPrice' => $object->isGroupPrice()
        ];
    }

    private function getNames(Tariff $tariff): array
    {
        $output = [];
        foreach ($tariff->getName()->getLanguages() as $language) {
            $output[$language->toString()] = $tariff->getName()->getTranslation($language)->toString();
        }
        return $output;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Tariff;
    }
}
