<?php

namespace CultuurNet\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

class SimpleDeserializerLocator implements DeserializerLocatorInterface
{
    /**
     * @var DeserializerInterface[]
     */
    protected $deserializers = [];

    /**
     * @param StringLiteral $contentType
     * @param DeserializerInterface $deserializer
     */
    public function registerDeserializer(
        StringLiteral $contentType,
        DeserializerInterface $deserializer
    ) {
        $this->deserializers[$contentType->toNative()] = $deserializer;
    }

    /**
     * @param StringLiteral $contentType
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
