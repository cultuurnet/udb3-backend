<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

class SimpleDeserializerLocator implements DeserializerLocatorInterface
{
    /**
     * @var DeserializerInterface[]
     */
    protected $deserializers = [];


    public function registerDeserializer(
        StringLiteral $contentType,
        DeserializerInterface $deserializer
    ) {
        $this->deserializers[$contentType->toNative()] = $deserializer;
    }

    /**
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType)
    {
        if (array_key_exists($contentType->toNative(), $this->deserializers)) {
            return $this->deserializers[$contentType->toNative()];
        }

        throw new DeserializerNotFoundException(
            "Unable to find a deserializer for content type '{$contentType->toNative()}'"
        );
    }
}
