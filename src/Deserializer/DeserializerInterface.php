<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerInterface
{
    public function deserialize(StringLiteral $data);
}
