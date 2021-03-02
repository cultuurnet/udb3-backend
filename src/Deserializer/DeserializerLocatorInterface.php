<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use ValueObjects\StringLiteral\StringLiteral;

interface DeserializerLocatorInterface
{
    /**
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType);
}
