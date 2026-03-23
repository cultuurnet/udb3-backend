<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class ClosedDay
{
    private \DateTimeImmutable $startDate;

    private \DateTimeImmutable $endDate;

    private ?TranslatedClosedDayDescription $description;

    public function __construct(
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?TranslatedClosedDayDescription $description = null
    ) {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('"startDate" should not be later than "endDate".');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->description = $description;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getDescription(): ?TranslatedClosedDayDescription
    {
        return $this->description;
    }
}
