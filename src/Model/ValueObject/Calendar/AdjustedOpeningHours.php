<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use DateTimeImmutable;
use InvalidArgumentException;

final class AdjustedOpeningHours
{
    public function __construct(
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate,
        private readonly OpeningHours $openingHours,
        private readonly ?TranslatedAdjustedOpeningHoursDescription $description = null
    ) {
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('"startDate" should not be later than "endDate".');
        }

        if ($openingHours->toArray() === []) {
            throw new InvalidArgumentException('AdjustedOpeningHours must contain at least one OpeningHour.');
        }
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }

    public function getDescription(): ?TranslatedAdjustedOpeningHoursDescription
    {
        return $this->description;
    }
}
