<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;

class Hour
{
    use IsInteger;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 23) {
            throw new \InvalidArgumentException('Hour should be an integer between 0 and 23.');
        }

        $this->setValue($value);
    }
}
