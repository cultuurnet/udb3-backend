<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedOpeningHoursCollection;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class AdjustedOpeningHoursDenormalizer implements DenormalizerInterface
{
    private TranslatedAdjustedOpeningHoursDescriptionDenormalizer $translatedDescriptionDenormalizer;

    public function __construct()
    {
        $this->translatedDescriptionDenormalizer = new TranslatedAdjustedOpeningHoursDescriptionDenormalizer();
    }

    public function denormalize($data, $class, $format = null, array $context = []): AdjustedOpeningHoursCollection
    {
        if (!is_array($data)) {
            return new AdjustedOpeningHoursCollection();
        }

        $adjustedOpeningHours = [];
        foreach ($data as $adjustedOpeningHoursData) {
            if (!is_array($adjustedOpeningHoursData) || !isset($adjustedOpeningHoursData['startDate'], $adjustedOpeningHoursData['endDate'], $adjustedOpeningHoursData['openingHours'])) {
                continue;
            }

            if (empty($adjustedOpeningHoursData['openingHours'])) {
                continue;
            }

            $startDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData['startDate']);
            $endDate = DateTimeFactory::fromDateOrISO8601($adjustedOpeningHoursData['endDate']);

            $openingHours = $this->denormalizeOpeningHours($adjustedOpeningHoursData['openingHours']);

            $description = null;
            if (isset($adjustedOpeningHoursData['description']) && is_array($adjustedOpeningHoursData['description'])) {
                $description = $this->translatedDescriptionDenormalizer->denormalize(
                    $adjustedOpeningHoursData['description'],
                    TranslatedAdjustedOpeningHoursDescription::class
                );
            }

            $adjustedOpeningHours[] = new AdjustedOpeningHours($startDate, $endDate, $openingHours, $description);
        }

        return new AdjustedOpeningHoursCollection(...$adjustedOpeningHours);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $type === AdjustedOpeningHoursCollection::class;
    }

    private function denormalizeOpeningHours(array $openingHoursData): OpeningHours
    {
        $denormalizer = new OpeningHourDenormalizer();
        $openingHours = array_map(
            fn (array $data) => $denormalizer->denormalize($data, OpeningHour::class),
            $openingHoursData
        );
        return new OpeningHours(...$openingHours);
    }
}
