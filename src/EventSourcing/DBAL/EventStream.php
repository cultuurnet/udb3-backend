<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventSourcing\EventStreamDecoratorInterface;
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\DBALException;

class EventStream
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SerializerInterface
     */
    protected $payloadSerializer;

    /**
     * @var SerializerInterface
     */
    protected $metadataSerializer;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var int
     */
    protected $startId;

    /**
     * @var int
     */
    protected $lastProcessedId;

    /**
     * @var string[]
     */
    protected $cdbids;

    /**
     * @var EventStreamDecoratorInterface
     */
    private $domainEventStreamDecorator;

    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @param Connection $connection
     * @param SerializerInterface $payloadSerializer
     * @param SerializerInterface $metadataSerializer
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $payloadSerializer,
        SerializerInterface $metadataSerializer,
        $tableName
    ) {
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName = $tableName;
        $this->startId = 0;
        $this->aggregateType = '';
    }

    /**
     * @param int $startId
     * @return EventStream
     */
    public function withStartId($startId)
    {
        if (!is_int($startId)) {
            throw new \InvalidArgumentException('StartId should have type int.');
        }

        if ($startId <= 0) {
            throw new \InvalidArgumentException('StartId should be higher than 0.');
        }

        $c = clone $this;
        $c->startId = $startId;
        return $c;
    }

    /**
     * @param string $aggregateType
     * @return EventStream
     */
    public function withAggregateType($aggregateType)
    {
        $c = clone $this;
        $c->aggregateType = $aggregateType;
        return $c;
    }


    /**
     * @param string[] $cdbids
     * @return EventStream
     */
    public function withCdbids($cdbids)
    {
        if (!is_array($cdbids)) {
            throw new \InvalidArgumentException('Cdbids should have type array.');
        }

        if (empty($cdbids)) {
            throw new \InvalidArgumentException('Cdbids can\'t be empty.');
        }

        $c = clone $this;
        $c->cdbids = $cdbids;
        return $c;
    }

    /**
     * @param EventStreamDecoratorInterface $domainEventStreamDecorator
     * @return EventStream
     */
    public function withDomainEventStreamDecorator(EventStreamDecoratorInterface $domainEventStreamDecorator)
    {
        $c = clone $this;
        $c->domainEventStreamDecorator = $domainEventStreamDecorator;
        return $c;
    }

    public function __invoke()
    {
        do {
            $statement = $this->prepareLoadStatement();

            $events = [];
            while ($row = $statement->fetch()) {
                $events[] = $this->deserializeEvent($row);
                $this->lastProcessedId = $row['id'];
                // Make sure to increment to prevent endless loop.
                $this->startId = $row['id'] + 1;
            }

            /* @var DomainMessage[] $events */
            if (!empty($events)) {
                $event = $events[0];
                $domainEventStream = new DomainEventStream($events);

                if (!is_null($this->domainEventStreamDecorator)) {
                    // Because the load statement always returns one row at a
                    // time, and we always wrap a single domain message in a
                    // stream as a result, we can simply get the aggregate type
                    // and aggregate id from the first domain message in the
                    // stream.
                    $domainEventStream = $this->domainEventStreamDecorator->decorateForWrite(
                        get_class($event->getPayload()),
                        $event->getId(),
                        $domainEventStream
                    );
                }

                yield $domainEventStream;
            }
        } while (!empty($events));
    }

    /**
     * @return int
     */
    public function getLastProcessedId()
    {
        return $this->lastProcessedId;
    }

    /**
     * The load statement can no longer be 'cached' because of using the query
     * builder. The query builder requires all parameters to be set before
     * using the execute command. The previous solution used the prepare
     * statement on the connection, this did not require all parameters to be
     * set up front.
     *
     * @return Statement
     * @throws DBALException
     */
    protected function prepareLoadStatement()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select('id', 'uuid', 'playhead', 'metadata', 'payload', 'recorded_on')
            ->from($this->tableName)
            ->where('id >= :startid')
            ->setParameter('startid', $this->startId)
            ->orderBy('id', 'ASC')
            ->setMaxResults(1);

        if ($this->cdbids) {
            $queryBuilder->andWhere('uuid IN (:uuids)')
                ->setParameter('uuids', $this->cdbids, Connection::PARAM_STR_ARRAY);
        }

        if (!empty($this->aggregateType)) {
            $queryBuilder->andWhere('aggregate_type = :aggregate_type');
            $queryBuilder->setParameter('aggregate_type', $this->aggregateType);
        }

        return $queryBuilder->execute();
    }

    private function deserializeEvent(array $row): DomainMessage
    {
        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode($row['metadata'], true)),
            $this->payloadSerializer->deserialize(json_decode($row['payload'], true)),
            DateTime::fromString($row['recorded_on'])
        );
    }
}
