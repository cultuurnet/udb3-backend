<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use CultuurNet\UDB3\StringLiteral;

interface DeserializerInterface
{
    /**
     * @return array|object
     */
    public function deserialize(StringLiteral $data);
}
