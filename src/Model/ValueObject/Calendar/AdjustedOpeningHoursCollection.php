<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

final class AdjustedOpeningHoursCollection
{
    /**
     * @var AdjustedOpeningHours[]
     */
    private array $adjustedOpeningHours;

    public function __construct(AdjustedOpeningHours ...$adjustedOpeningHours)
    {
        usort(
            $adjustedOpeningHours,
            fn (AdjustedOpeningHours $a, AdjustedOpeningHours $b) => $a->getStartDate() <=> $b->getStartDate()
        );

        $this->adjustedOpeningHours = $adjustedOpeningHours;
    }

    /**
     * @return AdjustedOpeningHours[]
     */
    public function toArray(): array
    {
        return $this->adjustedOpeningHours;
    }

    public function isEmpty(): bool
    {
        return count($this->adjustedOpeningHours) === 0;
    }

    public function count(): int
    {
        return count($this->adjustedOpeningHours);
    }
}
