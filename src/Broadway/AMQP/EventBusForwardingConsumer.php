<?php

namespace CultuurNet\BroadwayAMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
class EventBusForwardingConsumer extends AbstractConsumer
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @param AMQPStreamConnection $connection
     * @param EventBusInterface $eventBus
     * @param DeserializerLocatorInterface $deserializerLocator
     * @param StringLiteral $consumerTag
     * @param StringLiteral $exchangeName
     * @param StringLiteral $queueName
     * @param int $delay
     */
    public function __construct(
        AMQPStreamConnection $connection,
        EventBusInterface $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        $delay = 0
    ) {
        $this->eventBus = $eventBus;

        parent::__construct(
            $connection,
            $deserializerLocator,
            $consumerTag,
            $exchangeName,
            $queueName,
            $delay,
            'event bus'
        );
    }

    /**
     * @param mixed $deserializedMessage
     * @param array $context
     */
    protected function handle($deserializedMessage, array $context)
    {
        // If the deserializer did not return a DomainMessage yet, then
        // consider the returned value as the payload, and wrap it in a
        // DomainMessage.
        if (!$deserializedMessage instanceof DomainMessage) {
            $deserializedMessage = new DomainMessage(
                UUID::generateAsString(),
                0,
                new Metadata($context),
                $deserializedMessage,
                DateTime::now()
            );
        }

        $this->eventBus->publish(
            new DomainEventStream([$deserializedMessage])
        );
    }
}
