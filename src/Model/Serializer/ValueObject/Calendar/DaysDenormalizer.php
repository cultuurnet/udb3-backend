<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DaysDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): Days
    {
        return new Days(...array_map(fn ($day) => new Day($day), $data));
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === Days::class;
    }
}
