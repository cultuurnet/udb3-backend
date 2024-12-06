<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventPlaceHistoryRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use DateTimeInterface;
use Doctrine\DBAL\Connection;

class DBALEventPlaceHistoryRepository implements EventPlaceHistoryRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function storeEventPlaceStartingPoint(UUID $eventId, UUID $placeId, DateTimeInterface $date): void
    {
        $this->insertIntoPlaceHistoryTable($eventId, null, $placeId, $date);
    }

    public function storeEventPlaceMove(UUID $eventId, UUID $oldPlaceId, UUID $newPlaceId, DateTimeInterface $date): void
    {
        $this->insertIntoPlaceHistoryTable($eventId, $oldPlaceId, $newPlaceId, $date);
    }

    private function insertIntoPlaceHistoryTable(UUID $eventId, ?UUID $oldPlaceId, UUID $newPlaceId, DateTimeInterface $date): void
    {
        $this->connection->insert(
            'event_place_history',
            [
                'event' => $eventId->toString(),
                'old_place' => $oldPlaceId ? $oldPlaceId->toString() : null,
                'new_place' => $newPlaceId->toString(),
                'date' => $date->format(DateTimeInterface::ATOM),
            ]
        );
    }
}
