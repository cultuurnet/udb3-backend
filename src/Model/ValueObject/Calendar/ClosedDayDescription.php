<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\HasMaxLength;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class ClosedDayDescription
{
    use IsString;
    use IsNotEmpty;
    use HasMaxLength;

    private const MAX_LENGTH = 1000;

    public function __construct(string $value)
    {
        $this->guardNotEmpty($value);
        $this->setValue($value);
        $this->guardTooLong(ClosedDayDescription::class, $value, self::MAX_LENGTH);
    }
}
