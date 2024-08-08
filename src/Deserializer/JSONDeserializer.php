<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use JsonException;

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
    public function deserialize(string $data)
    {
        $data = json_decode($data, $this->assoc);

        if (null === $data) {
            throw new JsonException('Invalid JSON');
        }

        return $data;
    }
}
