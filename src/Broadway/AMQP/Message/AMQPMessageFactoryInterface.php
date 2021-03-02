<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Message;

use Broadway\Domain\DomainMessage;
use PhpAmqpLib\Message\AMQPMessage;

interface AMQPMessageFactoryInterface
{
    /**
     * @return AMQPMessage
     */
    public function createAMQPMessage(DomainMessage $domainMessage);
}
