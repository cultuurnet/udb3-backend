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
        if ($value > 65535) {
            throw new \InvalidArgumentException('Given int is not a valid port number.');
        }

        $this->setValue($value);
    }
}
