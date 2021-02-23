<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
class CommandBusForwardingConsumer extends AbstractConsumer
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(
        AMQPStreamConnection $connection,
        CommandBus $commandBus,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        int $delay = 0
    ) {
        $this->commandBus = $commandBus;

        parent::__construct(
            $connection,
            $deserializerLocator,
            $consumerTag,
            $exchangeName,
            $queueName,
            $delay,
            'command bus'
        );
    }

    /**
     * @param mixed $deserializedMessage
     * @param array $context
     */
    protected function handle($deserializedMessage, array $context)
    {
        $this->commandBus->dispatch($deserializedMessage);
    }
}
