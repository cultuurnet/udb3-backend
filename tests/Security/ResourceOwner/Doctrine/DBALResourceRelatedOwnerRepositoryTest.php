<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

final class DBALResourceRelatedOwnerRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALResourceRelatedOwnerRepository $repository;

    private string $organizersTable;

    private string $relationsTable;

    private string $organizerCreator;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->organizerCreator = Uuid::uuid4()->toString();

        $this->organizersTable = 'organizer_permission_readmodel';
        $this->relationsTable = 'event_relations';

        $idField = 'event';

        $this->repository = new DBALResourceRelatedOwnerRepository(
            $this->organizersTable,
            $this->relationsTable,
            $this->getConnection(),
            $idField
        );

        $this->seedOrganizers();
        $this->seedRelations();
    }

    /**
     * @test
     */
    public function testGetEditableResourceIds(): void
    {
        $this->assertEquals(
            ['event1', 'event3'],
            $this->repository->getEditableResourceIds($this->organizerCreator)
        );
    }

    private function seedOrganizers(): void
    {
        $this->insertOrganizer('organizer1', $this->organizerCreator);
        $this->insertOrganizer('organizer2', Uuid::uuid4()->toString());
        $this->insertOrganizer('organizer3', $this->organizerCreator);
    }

    private function seedRelations(): void
    {
        $this->insertRelation('event1', 'organizer1');
        $this->insertRelation('event2', 'organizer2');
        $this->insertRelation('event3', 'organizer3');
        $this->insertRelation('event4', null, 'place1');
    }

    private function insertOrganizer(string $organizerId, string $userId): void
    {
        $this->getConnection()->insert(
            $this->organizersTable,
            [
                'organizer_id' => $organizerId,
                'user_id' => $userId,
            ]
        );
    }

    private function insertRelation(string $eventId, string $organizerId = null, string $placeId = null): void
    {
        $this->getConnection()->insert(
            $this->relationsTable,
            [
                'event' => $eventId,
                'organizer' => $organizerId,
                'place' => $placeId,
            ]
        );
    }
}
