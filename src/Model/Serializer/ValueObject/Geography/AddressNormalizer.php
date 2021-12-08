<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AddressNormalizer implements NormalizerInterface
{
    /**
     * @param Address $address
     */
    public function normalize($address, $format = null, array $context = []): array
    {
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
