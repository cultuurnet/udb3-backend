<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use DateTimeImmutable;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BirthdateRangeDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): BirthdateRange
    {
        if (!$this->supportsDenormalization($data, $class, $format)) {
            throw new UnsupportedException("BirthdateRangeDenormalizer does not support {$class}.");
        }

        return new BirthdateRange(
            new DateTimeImmutable($data['from']),
            new DateTimeImmutable($data['to'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === BirthdateRange::class;
    }
}
