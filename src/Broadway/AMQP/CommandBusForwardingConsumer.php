<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
class CommandBusForwardingConsumer extends AbstractConsumer
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @param AMQPStreamConnection $connection
     * @param CommandBusInterface $commandBus
     * @param DeserializerLocatorInterface $deserializerLocator
     * @param StringLiteral $consumerTag
     * @param StringLiteral $exchangeName
     * @param StringLiteral $queueName
     * @param int $delay
     */
    public function __construct(
        AMQPStreamConnection $connection,
        CommandBusInterface $commandBus,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        $delay = 0
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
