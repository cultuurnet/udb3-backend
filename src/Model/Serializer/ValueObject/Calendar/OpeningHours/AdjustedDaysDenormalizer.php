<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\TranslatedAdjustedDescriptionDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDay;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\AdjustedDays;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AdjustedDaysDenormalizer implements DenormalizerInterface
{
    private OpeningHourDenormalizer $openingHourDenormalizer;
    private TranslatedAdjustedDescriptionDenormalizer $translatedDescriptionDenormalizer;

    public function __construct()
    {
        $this->openingHourDenormalizer = new OpeningHourDenormalizer();
        $this->translatedDescriptionDenormalizer = new TranslatedAdjustedDescriptionDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = []): AdjustedDays
    {
        $adjustedDays = [];
        foreach ($data as $adjustedOpeningHoursData) {
            if (empty($adjustedOpeningHoursData['openingHours'])) {
                continue;
            }

            $startDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData['startDate']);
            $endDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData['endDate']);

            $openingHours = $this->denormalizeOpeningHours($adjustedOpeningHoursData['openingHours']);

            $description = null;
            if (!empty($adjustedOpeningHoursData['description']) && is_array($adjustedOpeningHoursData['description'])) {
                $description = $this->translatedDescriptionDenormalizer->denormalize(
                    $adjustedOpeningHoursData['description'],
                    TranslatedAdjustedDescription::class
                );
            }

            $adjustedDays[] = new AdjustedDay($startDate, $endDate, $openingHours, $description);
        }

        return new AdjustedDays(...$adjustedDays);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AdjustedDays::class;
    }

    private function denormalizeOpeningHours(array $openingHoursData): OpeningHours
    {
        $openingHours = array_map(
            fn (array $data) => $this->openingHourDenormalizer->denormalize($data, OpeningHour::class),
            $openingHoursData
        );
        return new OpeningHours(...$openingHours);
    }
}
