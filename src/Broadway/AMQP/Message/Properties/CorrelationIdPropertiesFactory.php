<?php

namespace CultuurNet\BroadwayAMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

class CorrelationIdPropertiesFactory implements PropertiesFactoryInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage)
    {
        return ['correlation_id' => $domainMessage->getId() . '-' . $domainMessage->getPlayhead()];
    }
}
