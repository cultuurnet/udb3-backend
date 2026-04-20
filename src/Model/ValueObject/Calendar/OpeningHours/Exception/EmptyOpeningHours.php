<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Exception;

use InvalidArgumentException;

final class EmptyOpeningHours extends InvalidArgumentException
{
    public static function create(): self
    {
        return new self('openingHours must not be empty');
    }
}
