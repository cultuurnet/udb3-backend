<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class AdjustedDays extends Collection
{
    /**
     * @var AdjustedDay[]
     */
    private array $values;

    public function __construct(AdjustedDay ...$adjustedDays)
    {
        $startDates = array_map(
            fn (AdjustedDay $entry) => $entry->getStartDate()->format('Y-m-d'),
            $adjustedDays
        );

        if (count($startDates) !== count(array_unique($startDates))) {
            throw new \InvalidArgumentException('OpeningHoursAdjustedPeriods cannot contain two entries with the same start date.');
        }

        usort(
            $adjustedDays,
            fn (AdjustedDay $a, AdjustedDay $b) => $a->getStartDate() <=> $b->getStartDate()
        );

        $this->values = $adjustedDays;
    }

    /**
     * @return AdjustedDay[]
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function isEmpty(): bool
    {
        return count($this->values) === 0;
    }

    public function count(): int
    {
        return count($this->values);
    }
}
