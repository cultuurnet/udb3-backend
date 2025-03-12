<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ReadModel\Exception\RoleNotFound;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use PHPUnit\Framework\TestCase;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALRepository $dbalRepository;

    private array $role;

    private string $tableName;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->tableName = 'roles_search_v3';

        $this->dbalRepository = new DBALRepository(
            $this->getConnection(),
            $this->tableName
        );

        $this->role = [
            'uuid' => '8d17cffe-6f28-459c-8627-1f6345f8b296',
            'name' => 'Leuven validatoren',
            'constraint_query' => 'city:Leuven',
        ];
    }

    /**
     * @test
     */
    public function it_can_save(): void
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
    public function it_can_update_a_role_constraint(): void
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
    public function it_can_update_a_role_name(): void
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
    public function it_can_remove(): void
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
    public function it_can_search(): void
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

        $expectedRoles = [
            $expectedRole1,
            $expectedRole2,
            $expectedRole3,
        ];

        foreach ($expectedRoles as $role) {
            $this->dbalRepository->save(
                $role['uuid'],
                $role['name'],
                'foo:bar'
            );
        }

        // Search everything, results are sorted alphabetically and case-insensitive.
        $this->connection->beginTransaction();
        $actualResults = $this->dbalRepository->search();
        $this->connection->rollBack();

        $this->assertEquals(
            [
                $expectedRole2,
                $expectedRole3,
                $expectedRole1,
            ],
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

        // Search everything, results are sorted alphabetically and case-insensitive.
        $this->connection->beginTransaction();
        $actualResults = $this->dbalRepository->search('validator', 5);
        $this->connection->rollBack();

        $this->assertEquals(
            [
                $expectedRole2,
                $expectedRole3,
            ],
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

    /** @test */
    public function it_returns_a_role_with_a_role_id(): void
    {
        $roleId = Uuid::uuid4();
        $name = 'my secret club';
        $constraint = 'foo:bar';

        $this->dbalRepository->save(
            $roleId->toString(),
            $name,
            $constraint
        );

        $role = $this->dbalRepository->load($roleId);

        $this->assertEquals($roleId, $role->getUuid());
        $this->assertEquals($name, $role->getName());
        $this->assertEquals(new Query($constraint), $role->getConstraintQuery());
    }

    /** @test */
    public function it_throws_an_exception_when_it_cannot_load_a_role(): void
    {
        $roleId = Uuid::uuid4();

        $this->expectException(RoleNotFound::class);

        $this->dbalRepository->load($roleId);
    }

    protected function getLastRole(): ?array
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $rows = $statement->fetchAllAssociative();

        return $rows ? $rows[count($rows) - 1] : null;
    }
}
