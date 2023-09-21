<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

class SimpleDeserializerLocator implements DeserializerLocatorInterface
{
    /**
     * @var DeserializerInterface[]
     */
    protected array $deserializers = [];

    public function registerDeserializer(
        string $contentType,
        DeserializerInterface $deserializer
    ): void {
        $this->deserializers[$contentType] = $deserializer;
    }

    public function getDeserializerForContentType(string $contentType): DeserializerInterface
    {
        if (array_key_exists($contentType, $this->deserializers)) {
            return $this->deserializers[$contentType];
        }

        throw new DeserializerNotFoundException(
            "Unable to find a deserializer for content type '{$contentType}'"
        );
    }
}
