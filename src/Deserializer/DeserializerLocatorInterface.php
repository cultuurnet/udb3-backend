<?php

namespace CultuurNet\UDB3\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerLocatorInterface
{
    /**
     * @param StringLiteral $contentType
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType);
}
