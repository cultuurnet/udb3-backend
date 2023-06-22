<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use CultuurNet\UDB3\StringLiteral;

class JSONDeserializer implements DeserializerInterface
{
    private bool $assoc;

    public function __construct(bool $assoc = false)
    {
        $this->assoc = $assoc;
    }

    /**
     * Decodes a JSON string into a generic PHP object.
     *
     * @return object|object[]
     */
    public function deserialize(StringLiteral $data)
    {
        $data = json_decode($data->toNative(), $this->assoc);

        if (null === $data) {
            throw new NotWellFormedException('Invalid JSON');
        }

        return $data;
    }
}
