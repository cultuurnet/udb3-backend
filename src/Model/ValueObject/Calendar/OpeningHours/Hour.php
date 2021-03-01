<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;

class Hour
{
    use IsInteger;

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->guardInteger($value);

        if ($value < 0 || $value > 23) {
            throw new \InvalidArgumentException('Hour should be an integer between 0 and 23.');
        }

        $this->setValue($value);
    }
}
