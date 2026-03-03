<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStore;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\AggregateType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;

class AggregateAwareDBALEventStore implements EventStore
{
    private Connection $connection;

    private Serializer $payloadSerializer;

    private Serializer $metadataSerializer;

    private string $tableName;

    private string $aggregateType;

    public function __construct(
        Connection $connection,
        Serializer $payloadSerializer,
        Serializer $metadataSerializer,
        string $tableName,
        AggregateType $aggregateType
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName          = $tableName;
        $this->aggregateType      = $aggregateType->toString();
    }

    public function load($id): DomainEventStream
    {
        return $this->loadDomainEventStream($id, 0);
    }

    public function loadFromPlayhead($id, int $playhead): DomainEventStream
    {
        return $this->loadDomainEventStream($id, $playhead);
    }

    private function loadDomainEventStream(string $id, int $playhead): DomainEventStream
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue('uuid', $id);
        $statement->bindValue('playhead', $playhead);
        $statement->execute();

        $events = [];
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

    public function append($id, DomainEventStream $eventStream): void
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

    private function insertMessage(Connection $connection, DomainMessage $domainMessage): void
    {
        $data = [
            'uuid'           => (string) $domainMessage->getId(),
            'playhead'       => $domainMessage->getPlayhead(),
            'metadata'       => Json::encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
            'payload'        => Json::encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
            'recorded_on'    => $domainMessage->getRecordedOn()->toString(),
            'type'           => $domainMessage->getType(),
            'aggregate_type' => $this->aggregateType,
        ];

        $connection->insert($this->tableName, $data);
    }

    private function prepareLoadStatement(): Statement
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select(
            ['uuid', 'playhead', 'metadata', 'payload', 'recorded_on', 'aggregate_type']
        )
            ->from($this->tableName)
            ->where('uuid = :uuid')
            ->andWhere('playhead >= :playhead')
            ->orderBy('playhead', 'ASC');

        return $this->connection->prepare(
            $queryBuilder->getSQL()
        );
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

    /**
     * Ensure that an error will be thrown if the ID in the domain messages is
     * not something that can be converted to a string.
     *
     * If we let this move on without doing this DBAL will eventually
     * give us a hard time but the true reason for the problem will be
     * obfuscated.
     */
    private function guardStream(DomainEventStream $eventStream): void
    {
        foreach ($eventStream as $domainMessage) {
            /** @var DomainMessage $domainMessage */
            $id = (string) $domainMessage->getId();
        }
    }
}
