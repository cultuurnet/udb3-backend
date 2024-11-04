<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

class ContentTypePropertiesFactory implements PropertiesFactoryInterface
{
    private ContentTypeLookupInterface $contentTypeLookup;


    public function __construct(ContentTypeLookupInterface $contentTypeLookup)
    {
        $this->contentTypeLookup = $contentTypeLookup;
    }

    public function createProperties(DomainMessage $domainMessage): array
    {
        $payloadClassName = get_class($domainMessage->getPayload());
        $contentType = $this->contentTypeLookup->getContentType($payloadClassName);
        return ['content_type' => $contentType];
    }
}
