<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

interface DeserializerInterface
{
    /**
     * @return array|object
     */
    public function deserialize(string $data);
}
