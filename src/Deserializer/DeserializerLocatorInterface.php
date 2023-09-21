<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

interface DeserializerLocatorInterface
{
    public function getDeserializerForContentType(string $contentType): DeserializerInterface;
}
