<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use CultuurNet\UDB3\StringLiteral;

interface DeserializerInterface
{
    public function deserialize(StringLiteral $data);
}
