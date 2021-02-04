<?php

namespace CultuurNet\UDB3\Broadway\AMQP\Message\Properties;

use Broadway\Domain\DomainMessage;

interface PropertiesFactoryInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return array
     */
    public function createProperties(DomainMessage $domainMessage);
}
