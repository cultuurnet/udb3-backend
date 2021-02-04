<?php

namespace CultuurNet\BroadwayAMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

class ContentTypePropertiesFactory implements PropertiesFactoryInterface
{
    /**
     * @var ContentTypeLookupInterface
     */
    private $contentTypeLookup;

    /**
     * @param ContentTypeLookupInterface $contentTypeLookup
     */
    public function __construct(ContentTypeLookupInterface $contentTypeLookup)
    {
        $this->contentTypeLookup = $contentTypeLookup;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage)
    {
        $payloadClassName = get_class($domainMessage->getPayload());
        $contentType = $this->contentTypeLookup->getContentType($payloadClassName);
        return ['content_type' => $contentType];
    }
}
