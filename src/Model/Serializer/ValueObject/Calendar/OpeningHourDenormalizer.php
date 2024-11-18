<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class OpeningHourDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): OpeningHour
    {
        return new OpeningHour(
            (new DaysDenormalizer())->denormalize($data['dayOfWeek'], Days::class),
            Time::fromString($data['opens']),
            Time::fromString($data['closes'])
        );
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $data === OpeningHour::class;
    }
}
