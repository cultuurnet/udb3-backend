<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventLocationHistoryRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement as DriverStatement;

class DBALEventLocationHistoryRepository implements EventLocationHistoryRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeEventLocationStartingPoint(UUID $eventId, UUID $placeId): void
    {
        $insert = $this->prepareInsertStatement();
        $insert->bindValue('event_id', $eventId->toString());
        $insert->bindValue('old_place_id', null);
        $insert->bindValue('new_place_id', $placeId->toString());
        $insert->execute();
    }

    public function storeEventLocationMove(UUID $eventId, UUID $oldPlaceId, UUID $newPlaceId): void
    {
        $insert = $this->prepareInsertStatement();
        $insert->bindValue('event_id', $eventId->toString());
        $insert->bindValue('old_place_id', $oldPlaceId->toString());
        $insert->bindValue('new_place_id', $newPlaceId->toString());
        $insert->execute();
    }

    private function prepareInsertStatement(): DriverStatement
    {
        return $this->connection->prepare(
            'INSERT INTO `event_location_history` SET
              event = :event_id,
              old_place = :old_place_id,
              new_place = :new_place_id,
              date = now()'
        );
    }
}
