<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\DaysDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class OpeningHourDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = []): OpeningHour
    {
        $openingHour = new OpeningHour(
            (new DaysDenormalizer())->denormalize($data['dayOfWeek'], Days::class),
            Time::fromString($data['opens']),
            Time::fromString($data['closes'])
        );

        if (isset($data['childcare'])) {
            $childcareStart = $data['childcare']['start'] ?? null;
            $childcareEnd = $data['childcare']['end'] ?? null;
            if ($childcareStart !== null || $childcareEnd !== null) {
                $start = $childcareStart !== null ? Time::fromString($childcareStart) : null;
                $end = $childcareEnd !== null ? Time::fromString($childcareEnd) : null;
                $openingHour = $openingHour->withChildcareTimeRange(new TimeImmutableRange($start, $end));
            }
        }

        return $openingHour;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === OpeningHour::class;
    }
}
