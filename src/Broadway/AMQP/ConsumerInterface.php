<?php

namespace CultuurNet\BroadwayAMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    /**
     * @param AMQPMessage $message
     */
    public function consume(AMQPMessage $message);

    /**
     * @return AMQPChannel
     */
    public function getChannel();
}
