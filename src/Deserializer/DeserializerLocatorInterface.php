<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Deserializer;

use CultuurNet\UDB3\StringLiteral;

interface DeserializerLocatorInterface
{
    /**
     * @return DeserializerInterface
     */
    public function getDeserializerForContentType(StringLiteral $contentType);
}
