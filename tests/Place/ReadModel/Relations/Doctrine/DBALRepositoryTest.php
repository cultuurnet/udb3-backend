<?php

namespace CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $tableName;

    protected function setUp()
    {
        $this->repository = new DBALRepository($this->getConnection());

        $schemaManager = $this->getConnection()->getSchemaManager();
        $schema = $schemaManager->createSchema();

        $schemaManager->createTable(
            $this->repository->configureSchema($schema)
        );

        $this->tableName = 'place_relations';
    }

    /**
     * @test
     */
    public function it_stores_a_place_organizer_relation()
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
    public function it_removes_a_relation_based_on_place_id()
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
    public function it_gets_all_places_based_on_organizer()
    {
        $this->seedPlaceRelations($this->repository);
        $actualPlaces = $this->repository->getPlacesOrganizedByOrganizer('organizerId2');

        $expectedPlaces = ['placeId3', 'placeId4'];

        $this->assertEquals($expectedPlaces, $actualPlaces);
    }

    /**
     * @param RepositoryInterface $repository
     * @return array
     */
    private function seedPlaceRelations(RepositoryInterface $repository)
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

    /**
     * @param Connection $connection
     * @return array
     */
    private function getAll(Connection $connection)
    {
        $queryBuilder = $connection->createQueryBuilder();

        $sql = $queryBuilder
            ->select('place', 'organizer')
            ->from($this->tableName)
            ->getSQL();

        return $connection->fetchAll($sql);
    }
}
