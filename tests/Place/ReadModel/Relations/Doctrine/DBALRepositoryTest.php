<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALPlaceRelationsRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $tableName;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->repository = new DBALPlaceRelationsRepository($this->getConnection());

        $this->tableName = 'place_relations';
    }

    /**
     * @test
     */
    public function it_stores_a_place_organizer_relation(): void
    {
        $expectedRelations = [
            ['place' => 'placeId', 'organizer' => 'organizerId'],
        ];
        $this->repository->storeRelations('placeId', 'organizerId');

        $actualRelations = $this->getAll($this->connection);

        $this->assertEquals($expectedRelations, $actualRelations);
    }

    /**
     * @test
     */
    public function it_removes_a_relation_based_on_place_id(): void
    {
        $storedRelations = $this->seedPlaceRelations($this->repository);

        $this->repository->removeRelations('placeId5');

        $actualRelations = $this->getAll($this->connection);

        unset($storedRelations[4]);

        $this->assertEquals($storedRelations, $actualRelations);
    }

    /**
     * @test
     */
    public function it_gets_all_places_based_on_organizer(): void
    {
        $this->seedPlaceRelations($this->repository);
        $actualPlaces = $this->repository->getPlacesOrganizedByOrganizer('organizerId2');

        $expectedPlaces = ['placeId3', 'placeId4'];

        $this->assertEquals($expectedPlaces, $actualPlaces);
    }

    /**
     * @return array
     */
    private function seedPlaceRelations(PlaceRelationsRepository $repository)
    {
        $relations = [
            ['place' => 'placeId1', 'organizer' => 'organizerId1'],
            ['place' => 'placeId2', 'organizer' => 'organizerId1'],
            ['place' => 'placeId3', 'organizer' => 'organizerId2'],
            ['place' => 'placeId4', 'organizer' => 'organizerId2'],
            ['place' => 'placeId5', 'organizer' => 'organizerId3'],
        ];

        foreach ($relations as $relation) {
            $repository->storeRelations(
                $relation['place'],
                $relation['organizer']
            );
        }

        return $relations;
    }

    private function getAll(Connection $connection): array
    {
        $queryBuilder = $connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select('place', 'organizer')
            ->from($this->tableName)
            ->getSQL();

        return $connection->fetchAllAssociative($sql);
    }
}
