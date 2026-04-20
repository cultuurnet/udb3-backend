<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception;

use InvalidArgumentException;

final class StartDateAfterEndDateException extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('startDate should not be later than endDate');
    }
}
