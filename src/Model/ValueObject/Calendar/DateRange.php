<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class DateRange
{
    private \DateTimeImmutable $from;

    private \DateTimeImmutable $to;

    public function __construct(\DateTimeImmutable $from, \DateTimeImmutable $to)
    {
        if ($from > $to) {
            throw new \InvalidArgumentException('"From" date should not be later than the "to" date.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return int
     *   Negative if this date range is less than the given date range.
     *   Positive if this date range is greater than the given date range.
     *   Zero if both ranges are the same.
     */
    public function compare(DateRange $dateRange): int
    {
        if ($this->getFrom() < $dateRange->getFrom()) {
            return -1;
        }

        if ($this->getFrom() > $dateRange->getFrom()) {
            return +1;
        }

        if ($this->getTo() < $dateRange->getTo()) {
            return -1;
        }

        if ($this->getTo() > $dateRange->getTo()) {
            return +1;
        }

        return 0;
    }

    public function getFrom(): \DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): \DateTimeImmutable
    {
        return $this->to;
    }
}
