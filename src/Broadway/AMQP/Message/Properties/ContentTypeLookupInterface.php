<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

interface ContentTypeLookupInterface
{
    public function withContentType(string $payloadClass, string $contentType): ContentTypeLookupInterface;

    public function getContentType(string $payloadClass): string;
}
