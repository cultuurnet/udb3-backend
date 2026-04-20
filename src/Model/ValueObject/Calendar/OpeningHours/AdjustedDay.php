<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception\EmptyOpeningHoursException;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception\StartDateAfterEndDateException;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use DateTimeImmutable;

final class AdjustedDay
{
    public function __construct(
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate,
        private readonly OpeningHours $openingHours,
        private readonly ?TranslatedAdjustedDescription $description = null
    ) {
        if ($startDate > $endDate) {
            throw StartDateAfterEndDateException::create();
        }

        if ($openingHours->isEmpty()) {
            throw EmptyOpeningHoursException::create();
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

    public function getDescription(): ?TranslatedAdjustedDescription
    {
        return $this->description;
    }
}
