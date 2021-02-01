<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\DBALEventStoreException;
use Broadway\EventStore\EventStoreInterface;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Serializer\SerializerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Event store making use of Doctrine DBAL and aware of the aggregate type.
 *
 * Based on Broadways DBALEventStore.
 */
class AggregateAwareDBALEventStore implements EventStoreInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $payloadSerializer;

    /**
     * @var SerializerInterface
     */
    private $metadataSerializer;

    /**
     * @var null
     */
    private $loadStatement = null;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $aggregateType;

    /**
     * @param Connection $connection
     * @param SerializerInterface $payloadSerializer
     * @param SerializerInterface $metadataSerializer
     * @param string $tableName
     * @param mixed $aggregateType
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $payloadSerializer,
        SerializerInterface $metadataSerializer,
        $tableName,
        $aggregateType
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName          = $tableName;
        $this->aggregateType      = (string) $aggregateType;
    }

    /**
     * {@inheritDoc}
     */
    public function load($id)
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue('uuid', $id);
        $statement->execute();

        $events = array();
        while ($row = $statement->fetch()) {
            // Drop events that do not match the aggregate type.
            if ($row['aggregate_type'] !== $this->aggregateType) {
                continue;
            }
            $events[] = $this->deserializeEvent($row);
        }

        if (empty($events)) {
            throw new EventStreamNotFoundException(sprintf('EventStream not found for aggregate with id %s', $id));
        }

        return new DomainEventStream($events);
    }

    /**
     * {@inheritDoc}
     */
    public function append($id, DomainEventStreamInterface $eventStream)
    {
        // The original Broadway DBALEventStore implementation did only check
        // the type of $id. It is better to test all UUIDs inside the event
        // stream.
        $this->guardStream($eventStream);

        // Make the transaction more robust by using the transactional statement.
        $this->connection->transactional(function (Connection $connection) use ($eventStream) {
            try {
                foreach ($eventStream as $domainMessage) {
                    $this->insertMessage($connection, $domainMessage);
                }
            } catch (DBALException $exception) {
                throw DBALEventStoreException::create($exception);
            }
        });
    }

    /**
     * @param Connection $connection
     * @param DomainMessage $domainMessage
     */
    private function insertMessage(Connection $connection, DomainMessage $domainMessage)
    {
        $data = array(
            'uuid'           => (string) $domainMessage->getId(),
            'playhead'       => $domainMessage->getPlayhead(),
            'metadata'       => json_encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
            'payload'        => json_encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
            'recorded_on'    => $domainMessage->getRecordedOn()->toString(),
            'type'           => $domainMessage->getType(),
            'aggregate_type' => $this->aggregateType,
        );

        $connection->insert($this->tableName, $data);
    }

    /**
     * @param Schema $schema
     * @return Table|null
     */
    public function configureSchema(Schema $schema)
    {
        if ($schema->hasTable($this->tableName)) {
            return null;
        }

        return $this->configureTable();
    }

    /**
     * @return mixed
     */
    public function configureTable()
    {
        $schema = new Schema();

        $table = $schema->createTable($this->tableName);

        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('uuid', 'guid', array('length' => 36,));
        $table->addColumn('playhead', 'integer', array('unsigned' => true));
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', array('length' => 32));
        $table->addColumn('type', 'string', array('length' => 128));
        $table->addColumn('aggregate_type', 'string', array('length' => 128));

        $table->setPrimaryKey(array('id'));

        $table->addUniqueIndex(array('uuid', 'playhead'));

        $table->addIndex(['type']);
        $table->addIndex(['aggregate_type']);

        return $table;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|null
     */
    private function prepareLoadStatement()
    {
        if (null === $this->loadStatement) {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder->select(
                ['uuid', 'playhead', 'metadata', 'payload', 'recorded_on', 'aggregate_type']
            )
                ->from($this->tableName)
                ->where('uuid = :uuid')
                ->orderBy('playhead', 'ASC');

            $this->loadStatement = $this->connection->prepare(
                $queryBuilder->getSQL()
            );
        }

        return $this->loadStatement;
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

    /**
     * Ensure that an error will be thrown if the ID in the domain messages is
     * not something that can be converted to a string.
     *
     * If we let this move on without doing this DBAL will eventually
     * give us a hard time but the true reason for the problem will be
     * obfuscated.
     *
     * @param DomainEventStreamInterface $eventStream
     */
    private function guardStream(DomainEventStreamInterface $eventStream)
    {
        foreach ($eventStream as $domainMessage) {
            /** @var DomainMessage $domainMessage */
            $id = (string) $domainMessage->getId();
        }
    }
}
