<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use RuntimeException;

final class RemainingCapacityExceedsCapacity extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('remainingCapacity must be less than or equal to capacity');
    }
}
