<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class Description
{
    use IsString;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
