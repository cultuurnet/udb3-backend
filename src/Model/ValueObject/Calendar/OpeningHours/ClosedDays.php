<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class ClosedDays extends Collection
{
    /**
     * @var ClosedDay[]
     */
    private array $closedDays;

    public function __construct(ClosedDay ...$closedDays)
    {
        usort(
            $closedDays,
            fn (ClosedDay $a, ClosedDay $b) => $a->getStartDate() <=> $b->getStartDate()
        );

        $this->closedDays = $closedDays;
    }

    /**
     * @return ClosedDay[]
     */
    public function toArray(): array
    {
        return $this->closedDays;
    }

    public function isEmpty(): bool
    {
        return count($this->closedDays) === 0;
    }

    public function count(): int
    {
        return count($this->closedDays);
    }
}
