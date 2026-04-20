<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception;

use InvalidArgumentException;

final class EmptyOpeningHoursException extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('OpeningHoursAdjusted must contain at least one OpeningHour.');
    }
}
