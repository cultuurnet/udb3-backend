<?php

namespace CultuurNet\UDB3\Role\ReadModel\Search\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALRepository
     */
    private $dbalRepository;

    /**
     * @var array
     */
    private $role;

    /**
     * @var StringLiteral
     */
    private $tableName;

    protected function setUp()
    {
        $this->tableName = new StringLiteral('test_roles_search');

        $schemaConfigurator = new SchemaConfigurator($this->tableName);
        $schemaManager = $this->getConnection()->getSchemaManager();
        $schemaConfigurator->configure($schemaManager);

        $this->dbalRepository = new DBALRepository(
            $this->getConnection(),
            $this->getTableName()
        );

        $this->role = array(
            'uuid' => '8d17cffe-6f28-459c-8627-1f6345f8b296',
            'name' => 'Leuven validatoren',
            'constraint_query' => 'city:Leuven',
        );
    }

    /**
     * @test
     */
    public function it_can_save()
    {
        $expectedRole = $this->role;

        $this->dbalRepository->save(
            $expectedRole['uuid'],
            $expectedRole['name'],
            $expectedRole['constraint_query']
        );

        $actualRole = $this->getLastRole();

        $this->assertEquals($expectedRole, $actualRole);
    }

    /**
     * @test
     */
    public function it_can_update_a_role_contraint()
    {
        $expectedRole = $this->role;

        $this->dbalRepository->save(
            $expectedRole['uuid'],
            $expectedRole['name'],
            $expectedRole['constraint_query']
        );

        $expectedRole['constraint_query'] = 'zipcode:3000';

        $this->dbalRepository->updateConstraint(
            $expectedRole['uuid'],
            $expectedRole['constraint_query']
        );

        $actualRole = $this->getLastRole();

        $this->assertEquals($expectedRole, $actualRole);
    }

    /**
     * @test
     */
    public function it_can_update_a_role_name()
    {
        $expectedRole = $this->role;

        $this->dbalRepository->save(
            $expectedRole['uuid'],
            $expectedRole['name'],
            $expectedRole['constraint_query']
        );

        $expectedRole['name'] = 'new_role_name';

        $this->dbalRepository->updateName($expectedRole['uuid'], $expectedRole['name']);

        $actualRole = $this->getLastRole();

        $this->assertEquals($expectedRole, $actualRole);
    }

    /**
     * @test
     */
    public function it_can_remove()
    {
        $expectedRole = $this->role;

        $this->dbalRepository->save(
            $expectedRole['uuid'],
            $expectedRole['name'],
            $expectedRole['constraint_query']
        );

        $this->dbalRepository->remove($expectedRole['uuid']);

        $this->assertNull($this->getLastRole());
    }

    /**
     * @test
     */
    public function it_can_search()
    {
        $expectedRole1 = [
            'uuid' => '8d17cffe-6f28-459c-8627-1f6345f8b296',
            'name' => 'Leuven moderators',
        ];
        $expectedRole2 = [
            'uuid' => '2ca57542-3b60-4984-b03f-48eca3ce0d35',
            'name' => 'bar validator',
        ];
        $expectedRole3 = [
            'uuid' => '317eb972-fe60-47b9-88c9-bc2e70fdf7a5',
            'name' => 'foo validator',
        ];

        $expectedRoles = array(
            $expectedRole1,
            $expectedRole2,
            $expectedRole3,
        );

        foreach ($expectedRoles as $role) {
            $this->dbalRepository->save(
                $role['uuid'],
                $role['name'],
                'foo:bar'
            );
        }

        // Search everything, results are sorted alphabetically.
        $this->connection->beginTransaction();
        $actualResults = $this->dbalRepository->search();
        $this->connection->rollBack();

        $this->assertEquals(
            array(
                $expectedRole1,
                $expectedRole2,
                $expectedRole3,
            ),
            $actualResults->getMember()
        );

        $this->assertEquals(
            10,
            $actualResults->getItemsPerPage()
        );

        $this->assertEquals(
            3,
            $actualResults->getTotalItems()
        );

        // Search everything, results are sorted alphabetically.
        $this->connection->beginTransaction();
        $actualResults = $this->dbalRepository->search('validator', 5);
        $this->connection->rollBack();

        $this->assertEquals(
            array(
                $expectedRole2,
                $expectedRole3,
            ),
            $actualResults->getMember()
        );

        $this->assertEquals(
            5,
            $actualResults->getItemsPerPage()
        );

        $this->assertEquals(
            2,
            $actualResults->getTotalItems()
        );
    }

    /**
     * @return array
     */
    protected function getLastRole()
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ? $rows[count($rows) - 1] : null;
    }

    /**
     * @return StringLiteral
     */
    protected function getTableName()
    {
        return $this->tableName;
    }
}
