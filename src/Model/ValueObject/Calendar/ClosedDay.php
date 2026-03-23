<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use DateTimeImmutable;

final class ClosedDay
{
    public function __construct(
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate,
        private readonly ?TranslatedClosedDayDescription $description = null
    ) {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('"startDate" should not be later than "endDate".');
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

    public function getDescription(): ?TranslatedClosedDayDescription
    {
        return $this->description;
    }
}
