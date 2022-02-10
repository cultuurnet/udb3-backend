<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;
use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsNatural;

class PortNumber
{
    use IsInteger;
    use IsNatural;

    public function __construct(int $value)
    {
        $this->guardNatural($value);
        $options = [
            'options' => [
                'min_range' => 0,
                'max_range' => 65535,
            ],
        ];

        if (!filter_var($value, FILTER_VALIDATE_INT, $options)) {
            throw new \InvalidArgumentException('Port Number should be an integer between 0 and 65535.');
        }

        $this->setValue($value);
    }
}
