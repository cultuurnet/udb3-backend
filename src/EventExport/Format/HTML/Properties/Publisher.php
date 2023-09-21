<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Trims;

class Publisher
{
    use IsString;
    use Trims;

    public function __construct(string $value)
    {
        $value = $this->trim($value);
        $this->setValue($value);
    }
}
