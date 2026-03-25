<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\ClosedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedClosedDayDescription;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ClosedDaysDenormalizer implements DenormalizerInterface
{
    private TranslatedClosedDayDescriptionDenormalizer $translatedClosedDayDescriptionDenormalizer;

    public function __construct()
    {
        $this->translatedClosedDayDescriptionDenormalizer = new TranslatedClosedDayDescriptionDenormalizer();
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

            $startDate = $this->parseDate($closedDayData['startDate']);
            $endDate = $this->parseDate($closedDayData['endDate']);

            $description = null;
            if (isset($closedDayData['description']) && is_array($closedDayData['description'])) {
                $description = $this->translatedClosedDayDescriptionDenormalizer->denormalize(
                    $closedDayData['description'],
                    TranslatedClosedDayDescription::class
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

    private function parseDate(string $dateString): DateTimeImmutable
    {
        $dateOnly = DateTimeImmutable::createFromFormat('Y-m-d|', $dateString);
        if ($dateOnly instanceof DateTimeImmutable) {
            return $dateOnly;
        }

        return DateTimeFactory::fromISO8601($dateString);
    }
}
