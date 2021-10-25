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
}
