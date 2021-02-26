<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public function consume(AMQPMessage $message);

    /**
     * @return AMQPChannel
     */
    public function getChannel();
}
