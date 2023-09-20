<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AddressNormalizer implements NormalizerInterface
{
    public function normalize($address, $format = null, array $context = []): array
    {
        if (! $address instanceof Address) {
            throw new InvalidArgumentException('Expected Address, got ' . get_class($address));
        }

        return [
            'addressCountry' => $address->getCountryCode()->getCode(),
            'addressLocality' => $address->getLocality()->toString(),
            'postalCode' => $address->getPostalCode()->toString(),
            'streetAddress' => $address->getStreet()->toString(),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data === Address::class;
    }
}
