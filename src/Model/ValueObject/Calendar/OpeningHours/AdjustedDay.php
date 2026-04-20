<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception\EmptyOpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception\StartDateAfterEndDate;
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
            throw StartDateAfterEndDate::create();
        }

        if ($openingHours->isEmpty()) {
            throw EmptyOpeningHours::create();
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
