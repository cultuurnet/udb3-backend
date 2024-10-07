<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Serializers;

use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DescriptionDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): Description
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("DescriptionDenormalizer does not support {$class}.");
        }

        if (!is_array($data)) {
            throw new UnsupportedException('Description data should be an associative array.');
        }

        return new Description($data['description']);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Description::class;
    }
}
