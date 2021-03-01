<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

interface ContentTypeLookupInterface
{
    /**
     * @param string $payloadClass
     * @param string $contentType
     * @return static
     */
    public function withContentType($payloadClass, $contentType);

    /**
     * @param string $payloadClass
     * @return string
     */
    public function getContentType($payloadClass);
}
