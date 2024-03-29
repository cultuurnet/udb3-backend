<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class CommandBusForwardingConsumer extends AbstractConsumer
{
    private CommandBus $commandBus;

    public function __construct(
        AMQPStreamConnection $connection,
        CommandBus $commandBus,
        DeserializerLocatorInterface $deserializerLocator,
        string $consumerTag,
        string $exchangeName,
        string $queueName,
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
     * @param null|object|string $deserializedMessage
     */
    protected function handle($deserializedMessage, array $context): void
    {
        $this->commandBus->dispatch($deserializedMessage);
    }
}
