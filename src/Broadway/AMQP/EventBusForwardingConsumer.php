<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Deserializer\DeserializerLocatorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactory;

final class EventBusForwardingConsumer extends AbstractConsumer
{
    private EventBus $eventBus;

    private UuidFactory $uuidFactory;

    private Connection $dbalConnection;

    public function __construct(
        AMQPStreamConnection $connection,
        EventBus $eventBus,
        DeserializerLocatorInterface $deserializerLocator,
        string $consumerTag,
        string $exchangeName,
        String $queueName,
        UuidFactory $uuidFactory,
        Connection $dbalConnection,
        int $delay = 0
    ) {
        $this->eventBus = $eventBus;
        $this->uuidFactory = $uuidFactory;
        $this->dbalConnection = $dbalConnection;

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
        $this->ensureDatabaseConnection();

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

    private function ensureDatabaseConnection(): void
    {
        try {
            if (!$this->dbalConnection->isConnected()) {
                $connected = $this->dbalConnection->connect();
                if (!$connected) {
                    $this->logger->critical('Reconnection to database failed');
                } else {
                    $this->logger->debug('Connection to database restored successfully');
                }
            } else {
                $this->logger->debug('Connection to database successfully verified');
            }
        } catch (Exception $exception) {
            $this->logger->critical('Connection checks to database failed with exception:' . $exception->getMessage());
        }
    }
}
