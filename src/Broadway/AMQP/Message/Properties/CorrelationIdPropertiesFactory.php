<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

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
