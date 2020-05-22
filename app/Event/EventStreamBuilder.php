<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use Broadway\Serializer\SerializerInterface;
use Broadway\Serializer\SimpleInterfaceSerializer;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use Doctrine\DBAL\Connection;
use ValueObjects\Enum\Enum;

class EventStreamBuilder
{
    /**
     * @var EventStream
     */
    private $eventStream;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $payloadSerializer;

    public function __construct(
        Connection $connection,
        SerializerInterface $payloadSerializer
    ) {
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
    }

    public function build(): EventStream
    {
        $this->eventStream = new EventStream(
            $this->connection,
            $this->payloadSerializer,
            new SimpleInterfaceSerializer(),
            'event_store'
        );
        return $this->eventStream;
    }

    public function withStartId(int $startId): EventStream
    {
        return $this->eventStream->withStartId($startId);
    }

    public function withAggregateType(Enum $aggregateType): EventStream
    {
        return $this->eventStream->withAggregateType($aggregateType->toNative());
    }

    public function withCdbids($cdbids): EventStream
    {
        return $this->eventStream->withCdbids($cdbids);
    }

    public function stream(): EventStream
    {
        return $this->eventStream;
    }
}
