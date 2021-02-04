<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use ValueObjects\Number\Natural;
use ValueObjects\StringLiteral\StringLiteral;

class EventBusForwardingConsumerFactory
{
    /**
     * Delay the consumption of UDB2 updates with some seconds to prevent a
     * race condition with the UDB3 worker. Modifications initiated by
     * commands in the UDB3 queue worker need to finish before their
     * counterpart UDB2 update is processed.
     *
     * @var Natural
     */
    protected $executionDelay;

    /**
     * @var AMQPStreamConnection
     */
    protected $connection;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DeserializerLocatorInterface
     */
    protected $deserializerLocator;

    /**
     * @var EventBusInterface
     */
    protected $eventBus;

    /**
     * @var StringLiteral
     */
    protected $consumerTag;

    /**
     * EventBusForwardingConsumerFactory constructor.
     * @param Natural $executionDelay
     * @param AMQPStreamConnection $connection
     * @param LoggerInterface $logger
     * @param DeserializerLocatorInterface $deserializerLocator
     * @param EventBusInterface $eventBus
     * @param StringLiteral $consumerTag
     */
    public function __construct(
        Natural $executionDelay,
        AMQPStreamConnection $connection,
        LoggerInterface $logger,
        DeserializerLocatorInterface $deserializerLocator,
        EventBusInterface $eventBus,
        StringLiteral $consumerTag
    ) {
        $this->executionDelay = $executionDelay;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->deserializerLocator = $deserializerLocator;
        $this->eventBus = $eventBus;
        $this->consumerTag = $consumerTag;
    }

    /**
     * @param StringLiteral $exchange
     * @param StringLiteral $queue
     * @return EventBusForwardingConsumer
     */
    public function create(
        StringLiteral $exchange,
        StringLiteral $queue
    ) {
        $eventBusForwardingConsumer = new EventBusForwardingConsumer(
            $this->connection,
            $this->eventBus,
            $this->deserializerLocator,
            $this->consumerTag,
            $exchange,
            $queue,
            $this->executionDelay->toNative()
        );

        $eventBusForwardingConsumer->setLogger($this->logger);

        return $eventBusForwardingConsumer;
    }
}
