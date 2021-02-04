<?php

namespace CultuurNet\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerInterface
{
    public function deserialize(StringLiteral $data);
}
