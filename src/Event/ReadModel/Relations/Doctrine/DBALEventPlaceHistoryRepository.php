<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventPlaceHistoryRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

class DBALEventPlaceHistoryRepository implements EventPlaceHistoryRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeEventPlaceStartingPoint(UUID $eventId, UUID $placeId): void
    {
        $this->insertIntoPlaceHistoryTable($eventId, null, $placeId);
    }

    public function storeEventPlaceMove(UUID $eventId, UUID $oldPlaceId, UUID $newPlaceId): void
    {
        $this->insertIntoPlaceHistoryTable($eventId, $oldPlaceId, $newPlaceId);
    }

    public function insertIntoPlaceHistoryTable(UUID $eventId, ?UUID $oldPlaceId, UUID $newPlaceId): void
    {
        $currentTimestamp = new DateTimeImmutable();

        $this->connection->insert(
            'event_place_history',
            [
                'event' => $eventId->toString(),
                'old_place' => $oldPlaceId ? $oldPlaceId->toString() : null,
                'new_place' => $newPlaceId->toString(),
                'date' => $currentTimestamp->format('Y-m-d H:i:s'),
            ]
        );
    }
}
