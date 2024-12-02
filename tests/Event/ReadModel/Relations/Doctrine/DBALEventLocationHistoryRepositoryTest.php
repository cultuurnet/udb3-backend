<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

class DBALEventLocationHistoryRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALEventLocationHistoryRepository $repository;

    public function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALEventLocationHistoryRepository($this->connection);
    }

    /** @test  */
    public function should_store_event_location_starting_point(): void
    {
        $eventId = Uuid::uuid4();
        $placeId = Uuid::uuid4();

        $this->repository->storeEventLocationStartingPoint($eventId, $placeId);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM event_location_history'
        );

        $this->assertNotNull($result);
        $this->assertEquals($eventId->toString(), $result['event']);
        $this->assertNull($result['old_place']);
        $this->assertEquals($placeId->toString(), $result['new_place']);
    }

    /** @test  */
    public function should_store_event_location_move(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->repository->storeEventLocationMove($eventId, $oldPlaceId, $newPlaceId);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM event_location_history'
        );

        $this->assertNotNull($result);
        $this->assertEquals($eventId->toString(), $result['event']);
        $this->assertEquals($oldPlaceId->toString(), $result['old_place']);
        $this->assertEquals($newPlaceId->toString(), $result['new_place']);
    }
}
