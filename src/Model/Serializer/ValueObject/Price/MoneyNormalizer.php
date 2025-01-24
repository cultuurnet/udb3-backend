<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Price;

use InvalidArgumentException;
use Money\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof Money) {
            throw new InvalidArgumentException(sprintf('Invalid object type, expected %s, received %s.', Money::class, get_class($object)));
        }

        return [
            'amount' => $object->getAmount(),
            'currency' => $object->getCurrency()->getName(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Money;
    }
}
