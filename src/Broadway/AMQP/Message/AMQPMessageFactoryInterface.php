<?php

namespace CultuurNet\BroadwayAMQP\Message;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;

interface AMQPMessageFactoryInterface
{
    /**
     * @param DomainMessage $domainMessage
     * @return AMQPMessage
     */
    public function createAMQPMessage(DomainMessage $domainMessage);
}
