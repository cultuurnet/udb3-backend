<?php

namespace CultuurNet\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerLocatorInterface
{
    /**
     * @param StringLiteral $contentType
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType);
}
