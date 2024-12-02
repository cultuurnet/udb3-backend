<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventLocationHistoryRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

class DBALEventLocationHistoryRepository implements EventLocationHistoryRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeEventLocationStartingPoint(UUID $eventId, UUID $placeId): void
    {
        $this->insertIntoLocationHistoryTable($eventId, null, $placeId);
    }

    public function storeEventLocationMove(UUID $eventId, UUID $oldPlaceId, UUID $newPlaceId): void
    {
        $this->insertIntoLocationHistoryTable($eventId, $oldPlaceId, $newPlaceId);
    }

    public function insertIntoLocationHistoryTable(UUID $eventId, ?UUID $oldPlaceId, UUID $newPlaceId): void
    {
        $currentTimestamp = new DateTimeImmutable();

        $this->connection->insert('event_location_history',
            [
                'event' => $eventId->toString(),
                'old_place' => $oldPlaceId ? $oldPlaceId->toString() : null,
                'new_place' => $newPlaceId->toString(),
                'date' => $currentTimestamp->format('Y-m-d H:i:s')
            ]);
    }
}
