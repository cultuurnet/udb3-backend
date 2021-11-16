<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Offer;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AvailableFromDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, $format = null, array $context = []): DateTimeInterface
    {
        return new DateTimeImmutable($data['availableFrom']);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === DateTimeInterface::class;
    }
}
