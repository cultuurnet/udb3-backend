<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use CultuurNet\UDB3\Json;

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
        if ($this->assoc) {
            return Json::decodeAssociatively($data);
        }

        return Json::decode($data);
    }
}
