<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class OpeningHoursAdjustedPeriods
{
    /**
     * @var OpeningHoursAdjusted[]
     */
    private array $openingHoursAdjusted;

    public function __construct(OpeningHoursAdjusted ...$openingHoursAdjusted)
    {
        $startDates = array_map(
            fn (OpeningHoursAdjusted $entry) => $entry->getStartDate()->format('Y-m-d'),
            $openingHoursAdjusted
        );

        if (count($startDates) !== count(array_unique($startDates))) {
            throw new \InvalidArgumentException('OpeningHoursAdjustedPeriods cannot contain two entries with the same start date.');
        }

        usort(
            $openingHoursAdjusted,
            fn (OpeningHoursAdjusted $a, OpeningHoursAdjusted $b) => $a->getStartDate() <=> $b->getStartDate()
        );

        $this->openingHoursAdjusted = $openingHoursAdjusted;
    }

    /**
     * @return OpeningHoursAdjusted[]
     */
    public function toArray(): array
    {
        return $this->openingHoursAdjusted;
    }

    public function isEmpty(): bool
    {
        return count($this->openingHoursAdjusted) === 0;
    }

    public function count(): int
    {
        return count($this->openingHoursAdjusted);
    }
}
