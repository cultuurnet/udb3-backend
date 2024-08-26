<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

final class DBALEventRelationsRepository implements EventRelationsRepository
{
    private string $tableName = 'event_relations';
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeRelations(string $eventId, ?string $placeId, ?string $organizerId): void
    {
        $insert = $this->prepareInsertStatement();
        $insert->bindValue('event', $eventId);
        $insert->bindValue('place', $placeId);
        $insert->bindValue('organizer', $organizerId);
        $insert->execute();
    }

    public function removeOrganizer(string $eventId): void
    {
        $transaction = function ($connection) use ($eventId): void {
            if ($this->eventHasRelations($connection, $eventId)) {
                $this->updateEventRelation($connection, $eventId, 'organizer', null);
            }
        };

        $this->connection->transactional($transaction);
    }

    public function storeOrganizer(string $eventId, ?string $organizerId): void
    {
        $this->storeRelation($eventId, 'organizer', $organizerId);
    }

    public function storePlace(string $eventId, ?string $placeId): void
    {
        $this->storeRelation($eventId, 'place', $placeId);
    }

    private function storeRelation(string $eventId, string $relationType, ?string $itemId): void
    {
        $transaction = function ($connection) use ($eventId, $relationType, $itemId): void {
            if ($this->eventHasRelations($connection, $eventId)) {
                $this->updateEventRelation($connection, $eventId, $relationType, $itemId);
            } else {
                $this->createEventRelation($connection, $eventId, $relationType, $itemId);
            }
        };

        $this->connection->transactional($transaction);
    }

    private function createEventRelation(
        Connection $connection,
        string $eventId,
        string $relationType,
        ?string $itemId
    ): void {
        $q = $connection
            ->createQueryBuilder()
            ->insert($this->tableName)
            ->values(
                [
                    'event' => ':event_id',
                    $relationType => ':item_id',
                ]
            )
            ->setParameter('event_id', $eventId)
            ->setParameter('item_id', $itemId);

        $q->execute();
    }

    private function updateEventRelation(
        Connection $connection,
        string $eventId,
        string $relationType,
        ?string $itemId
    ): void {
        $q = $connection
            ->createQueryBuilder()
            ->update($this->tableName)
            ->where('event = :event_id')
            ->set($relationType, ':item_id')
            ->setParameter('event_id', $eventId)
            ->setParameter('item_id', $itemId);

        $q->execute();
    }

    private function eventHasRelations(
        Connection $connection,
        string $id
    ): bool {
        $q = $connection->createQueryBuilder();

        $q->select('1')
            ->from($this->tableName, 'relation')
            ->where('relation.event = :event_id')
            ->setParameter('event_id', $id);

        $result = $q->execute();
        $relations = $result->fetchAllAssociative();

        return count($relations) > 0;
    }

    private function prepareInsertStatement(): DriverStatement
    {
        $table = $this->connection->quoteIdentifier($this->tableName);
        return $this->connection->prepare(
            "INSERT INTO {$table} SET
              event = :event,
              place = :place,
              organizer = :organizer
            ON DUPLICATE KEY UPDATE
              place = :place,
              organizer=:organizer"
        );
    }

    public function getEventsLocatedAtPlace(string $placeId): array
    {
        $q = $this->connection->createQueryBuilder();
        $q->select('event')
          ->from($this->tableName)
          ->where('place = ?')
          ->setParameter(0, $placeId);

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] = $id;
        }

        return $events;
    }

    public function getEventsOrganizedByOrganizer(string $organizerId): array
    {
        $q = $this->connection->createQueryBuilder();
        $q
            ->select('event')
            ->from($this->tableName)
            ->where('organizer = ?')
            ->setParameter(0, $organizerId);

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] = $id;
        }

        return $events;
    }

    public function getPlaceOfEvent(string $eventId): ?string
    {
        return $this->getRelationOfEvent($eventId, 'place');
    }

    public function getOrganizerOfEvent(string $eventId): ?string
    {
        return $this->getRelationOfEvent($eventId, 'organizer');
    }

    private function getRelationOfEvent(string $eventId, string $eventType): ?string
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->select(['place', 'organizer'])
            ->from($this->tableName)
            ->where('event = :eventId')
            ->setParameter(':eventId', $eventId);

        $statement = $queryBuilder->execute();

        $rows = $statement->fetchAllAssociative();

        return isset($rows[0][$eventType]) ? $rows[0][$eventType] : null;
    }

    public function removeRelations(string $eventId): void
    {
        $q = $this->connection->createQueryBuilder();
        $q->delete($this->tableName)
            ->where('event = ?')
            ->setParameter(0, $eventId);

        $q->execute();
    }
}
