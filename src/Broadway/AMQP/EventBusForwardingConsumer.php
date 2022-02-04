<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Ramsey\Uuid\UuidFactoryInterface;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Forwards messages coming in via AMQP to an event bus.
 */
class EventBusForwardingConsumer extends AbstractConsumer
{
    /**
     * @var EventBus
     */
    private $eventBus;

    private UuidFactoryInterface $uuidFactory;

    public function __construct(
        AMQPStreamConnection $connection,
        EventBus $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        StringLiteral $consumerTag,
        StringLiteral $exchangeName,
        StringLiteral $queueName,
        UuidFactoryInterface $uuidFactory,
        int $delay = 0
    ) {
        $this->eventBus = $eventBus;
        $this->uuidFactory = $uuidFactory;

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


    protected function handle($deserializedMessage, array $context)
    {
        // If the deserializer did not return a DomainMessage yet, then
        // consider the returned value as the payload, and wrap it in a
        // DomainMessage.
        if (!$deserializedMessage instanceof DomainMessage) {
            $deserializedMessage = new DomainMessage(
                $this->uuidFactory->uuid4()->toString(),
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
