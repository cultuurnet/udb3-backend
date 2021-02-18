<?php

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
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
     * @var EventBus
     */
    protected $eventBus;

    /**
     * @var StringLiteral
     */
    protected $consumerTag;

    public function __construct(
        Natural $executionDelay,
        AMQPStreamConnection $connection,
        LoggerInterface $logger,
        DeserializerLocatorInterface $deserializerLocator,
        EventBus $eventBus,
        StringLiteral $consumerTag
    ) {
        $this->executionDelay = $executionDelay;
        $this->connection = $connection;
        $this->logger = $logger;
        $this->deserializerLocator = $deserializerLocator;
        $this->eventBus = $eventBus;
        $this->consumerTag = $consumerTag;
    }

    public function create(
        StringLiteral $exchange,
        StringLiteral $queue
    ):EventBusForwardingConsumer {
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
