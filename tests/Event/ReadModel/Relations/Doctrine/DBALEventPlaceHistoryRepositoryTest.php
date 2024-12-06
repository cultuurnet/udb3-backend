<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Ramsey\Uuid\Uuid as RamseyUuid;

class DBALEventPlaceHistoryRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;
    private const DATE_TIME_VALUE = '2024-01-01T12:30:00+00:00';

    private DBALEventPlaceHistoryRepository $repository;

    public function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALEventPlaceHistoryRepository($this->connection);
    }

    /** @test  */
    public function should_store_event_location_starting_point(): void
    {
        $eventId = $this->uuid4();
        $placeId = $this->uuid4();
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, self::DATE_TIME_VALUE);

        $this->repository->storeEventPlaceStartingPoint($eventId, $placeId, $date);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM event_place_history'
        );

        $this->assertNotNull($result);
        $this->assertEquals($eventId->toString(), $result['event']);
        $this->assertNull($result['old_place']);
        $this->assertEquals($placeId->toString(), $result['new_place']);
        $this->assertEquals($date->format(DateTimeInterface::ATOM), $result['date']);
    }

    /** @test  */
    public function should_store_event_location_move(): void
    {
        $eventId = $this->uuid4();
        $oldPlaceId = $this->uuid4();
        $newPlaceId = $this->uuid4();
        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, self::DATE_TIME_VALUE);

        $this->repository->storeEventPlaceMove($eventId, $oldPlaceId, $newPlaceId, $date);

        $result = $this->connection->fetchAssociative(
            'SELECT * FROM event_place_history'
        );

        $this->assertNotNull($result);
        $this->assertEquals($eventId->toString(), $result['event']);
        $this->assertEquals($oldPlaceId->toString(), $result['old_place']);
        $this->assertEquals($newPlaceId->toString(), $result['new_place']);
        $this->assertEquals($date->format(DateTimeInterface::ATOM), $result['date']);
    }

    /** @todo Remove with the refactor of III-6438  */
    private function uuid4(): UUID
    {
        return new UUID(RamseyUuid::uuid4()->toString());
    }
}
