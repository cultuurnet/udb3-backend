<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use Countable;

final class OpeningHoursAdjustedDays implements Countable
{
    /**
     * @var OpeningHoursAdjustedDay[]
     */
    private array $openingHoursAdjusted;

    public function __construct(OpeningHoursAdjustedDay ...$openingHoursAdjusted)
    {
        $startDates = array_map(
            fn (OpeningHoursAdjustedDay $entry) => $entry->getStartDate()->format('Y-m-d'),
            $openingHoursAdjusted
        );

        if (count($startDates) !== count(array_unique($startDates))) {
            throw new \InvalidArgumentException('OpeningHoursAdjustedPeriods cannot contain two entries with the same start date.');
        }

        usort(
            $openingHoursAdjusted,
            fn (OpeningHoursAdjustedDay $a, OpeningHoursAdjustedDay $b) => $a->getStartDate() <=> $b->getStartDate()
        );

        $this->openingHoursAdjusted = $openingHoursAdjusted;
    }

    /**
     * @return OpeningHoursAdjustedDay[]
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
