<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use CultuurNet\UDB3\Doctrine\DatabaseConnectionChecker;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;

final class EventBusForwardingConsumer extends AbstractConsumer
{
    private EventBus $eventBus;

    private UuidFactory $uuidFactory;

    private DatabaseConnectionChecker $databaseConnectionChecker;

    public function __construct(
        AMQPStreamConnection $connection,
        EventBus $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        string $consumerTag,
        string $exchangeName,
        String $queueName,
        UuidFactory $uuidFactory,
        DatabaseConnectionChecker $databaseConnectionChecker,
        int $delay = 0
    ) {
        $this->eventBus = $eventBus;
        $this->uuidFactory = $uuidFactory;
        $this->databaseConnectionChecker = $databaseConnectionChecker;

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
     * @param null|object|string $deserializedMessage
     */
    protected function handle($deserializedMessage, array $context): void
    {
        $this->databaseConnectionChecker->ensureConnection();

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
