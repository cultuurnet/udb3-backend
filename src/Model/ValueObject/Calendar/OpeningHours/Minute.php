<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;

class Minute
{
    use IsInteger;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 59) {
            throw new \InvalidArgumentException('Minute should be an integer between 0 and 59.');
        }

        $this->setValue($value);
    }
}
