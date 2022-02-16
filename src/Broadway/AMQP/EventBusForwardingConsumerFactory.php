<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidFactoryInterface;
use CultuurNet\UDB3\StringLiteral;

final class EventBusForwardingConsumerFactory
{
    /**
     * Delay the consumption of UDB2 updates with some seconds to prevent a
     * race condition with the UDB3 worker. Modifications initiated by
     * commands in the UDB3 queue worker need to finish before their
     * counterpart UDB2 update is processed.
     */
    private int $executionDelay;

    private AMQPStreamConnection $connection;

    private LoggerInterface $logger;

    private DeserializerLocatorInterface $deserializerLocator;

    private EventBus $eventBus;

    private StringLiteral $consumerTag;

    private UuidFactoryInterface $uuidFactory;

    public function __construct(
        int $executionDelay,
        AMQPStreamConnection $connection,
        LoggerInterface $logger,
        DeserializerLocatorInterface $deserializerLocator,
        EventBus $eventBus,
        StringLiteral $consumerTag,
        UuidFactoryInterface $uuidFactory
    ) {
        if ($executionDelay < 0) {
            throw new InvalidArgumentException('Execution delay should be zero or higher.');
        }

        $this->executionDelay = $executionDelay;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->deserializerLocator = $deserializerLocator;
        $this->eventBus = $eventBus;
        $this->consumerTag = $consumerTag;
        $this->uuidFactory = $uuidFactory;
    }

    public function create(
        StringLiteral $exchange,
        StringLiteral $queue
    ): EventBusForwardingConsumer {
        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $exchange,
            $queue,
            $this->uuidFactory,
            $this->executionDelay
        );

        $eventBusForwardingConsumer->setLogger($this->logger);

        return $eventBusForwardingConsumer;
    }
}
