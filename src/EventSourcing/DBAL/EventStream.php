<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventSourcing\EventStreamDecorator;
use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\Json;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\DBALException;

class EventStream
{
    protected Connection $connection;

    protected Serializer $payloadSerializer;

    protected Serializer $metadataSerializer;

    protected string $tableName;

    protected int $startId;

    protected int $lastProcessedId;

    /**
     * @var string[]
     */
    protected array $cdbids;

    private ?EventStreamDecorator $domainEventStreamDecorator;

    private string $aggregateType;

    public function __construct(
        Connection $connection,
        Serializer $payloadSerializer,
        Serializer $metadataSerializer,
        string $tableName
    ) {
        $this->connection = $connection;
        $this->payloadSerializer = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName = $tableName;
        $this->startId = 0;
        $this->lastProcessedId = 0;
        $this->cdbids = [];
        $this->domainEventStreamDecorator = null;
        $this->aggregateType = '';
    }

    public function withStartId(int $startId): EventStream
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

    public function withAggregateType(string $aggregateType): EventStream
    {
        $c = clone $this;
        $c->aggregateType = $aggregateType;
        return $c;
    }


    /**
     * @param string[] $cdbids
     */
    public function withCdbids(array $cdbids): EventStream
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

    public function withDomainEventStreamDecorator(EventStreamDecorator $domainEventStreamDecorator): EventStream
    {
        $c = clone $this;
        $c->domainEventStreamDecorator = $domainEventStreamDecorator;
        return $c;
    }

    public function __invoke(): \Generator
    {
        do {
            $statement = $this->prepareLoadStatement();

            $events = [];
            while ($row = $statement->fetch()) {
                $events[] = $this->deserializeEvent($row);
                $this->lastProcessedId = (int) $row['id'];
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

    public function getLastProcessedId(): int
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
     * @throws DBALException
     */
    protected function prepareLoadStatement(): Statement
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
            (int) $row['playhead'],
            $this->metadataSerializer->deserialize(Json::decodeAssociatively($row['metadata'])),
            $this->payloadSerializer->deserialize(Json::decodeAssociatively($row['payload'])),
            DateTime::fromString($row['recorded_on'])
        );
    }
}
