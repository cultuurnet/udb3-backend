<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ClosedDaysDenormalizer implements DenormalizerInterface
{
    private TranslatedAdjustedDescriptionDenormalizer $translatedAdjustedDescriptionDenormalizer;

    public function __construct()
    {
        $this->translatedAdjustedDescriptionDenormalizer = new TranslatedAdjustedDescriptionDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = []): ClosedDays
    {
        if (!is_array($data)) {
            return new ClosedDays();
        }

        $closedDays = [];
        foreach ($data as $closedDayData) {
            if (!is_array($closedDayData) || !isset($closedDayData['startDate'], $closedDayData['endDate'])) {
                continue;
            }

            $startDate = DateTimeFactory::fromDateOrISO8601($closedDayData['startDate']);
            $endDate = DateTimeFactory::fromDateOrISO8601($closedDayData['endDate']);

            $description = null;
            if (isset($closedDayData['description']) && is_array($closedDayData['description'])) {
                $description = $this->translatedAdjustedDescriptionDenormalizer->denormalize(
                    $closedDayData['description'],
                    TranslatedAdjustedDescription::class
                );
            }

            $closedDays[] = new ClosedDay($startDate, $endDate, $description);
        }

        return new ClosedDays(...$closedDays);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === ClosedDays::class;
    }
}
