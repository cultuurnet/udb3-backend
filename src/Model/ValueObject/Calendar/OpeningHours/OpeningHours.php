<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class OpeningHours extends Collection
{
    /**
     * @param OpeningHour[] ...$openingHours
     */
    public function __construct(OpeningHour ...$openingHours)
    {
        parent::__construct(...$openingHours);
    }

    public function isAlwaysOpen(): bool
    {
        return $this->isEmpty();
    }

    public function withoutChildcare(): self
    {
        return new self(
            ...array_map(
                fn (OpeningHour $openingHour) => $openingHour->withChildcareTimeRange(null),
                $this->toArray()
            )
        );
    }

    public function hasChildcare(): bool
    {
        foreach ($this->toArray() as $openingHour) {
            if ($openingHour->hasChildcare()) {
                return true;
            }
        }

        return false;
    }
}
