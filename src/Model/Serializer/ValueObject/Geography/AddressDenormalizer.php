<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AddressDenormalizer implements DenormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("AddressDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Address data should be an associative array.');
        }

        return new Address(
            new Street($data['streetAddress']),
            new PostalCode($data['postalCode']),
            new Locality($data['addressLocality']),
            new CountryCode($data['addressCountry'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Address::class;
    }
}
