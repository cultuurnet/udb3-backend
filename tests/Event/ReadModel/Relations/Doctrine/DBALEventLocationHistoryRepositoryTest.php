<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Ramsey\Uuid\Uuid as RamseyUuid;

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
        $eventId = $this->uuid4();
        $placeId = $this->uuid4();

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
        $eventId = $this->uuid4();
        $oldPlaceId = $this->uuid4();
        $newPlaceId = $this->uuid4();

        $this->repository->storeEventLocationMove($eventId, $oldPlaceId, $newPlaceId);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM event_location_history'
        );

        $this->assertNotNull($result);
        $this->assertEquals($eventId->toString(), $result['event']);
        $this->assertEquals($oldPlaceId->toString(), $result['old_place']);
        $this->assertEquals($newPlaceId->toString(), $result['new_place']);
    }

    /** @todo Remove with the refactor of III-6438  */
    private function uuid4(): UUID
    {
        return new UUID(RamseyUuid::uuid4()->toString());
    }
}
