<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class SearchByRoleIdAndPermissionsTest extends TestCase
{
    use DBALTestConnectionTrait;

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    /** @test */
    public function it_returns_the_correct_users(): void
    {
        $roleId = Uuid::uuid4();
        $roleWithWrongPermissionsId = Uuid::uuid4();

        $userId1 = '177e737d-27ed-4156-ae86-57b87030ed02';
        $userId2 = '8452a083-bfe8-4cd3-bea8-19bb322d7fd1';

        $this->getConnection()->insert(
            'role_permissions',
            [
                'role_id' => $roleId->toString(),
                'permission' => Permission::organisatiesBewerken()->toString(),
            ]
        );

        $this->getConnection()->insert(
            'role_permissions',
            [
                'role_id' => $roleWithWrongPermissionsId->toString(),
                'permission' => Permission::filmsAanmaken()->toString(),
            ]
        );

        $this->getConnection()->insert(
            'user_roles',
            [
                'role_id' => $roleWithWrongPermissionsId->toString(),
                'user_id' => Uuid::uuid4()->toString(),
            ]
        );

        $this->getConnection()->insert(
            'user_roles',
            [
                'role_id' => $roleId->toString(),
                'user_id' => $userId1,
            ]
        );

        $this->getConnection()->insert(
            'user_roles',
            [
                'role_id' => $roleId->toString(),
                'user_id' => $userId2,
            ]
        );

        $searchByRoleIdAndPermissions = new SearchByRoleIdAndPermissions($this->getConnection());
        $users = $searchByRoleIdAndPermissions->findAllUsers($roleId, [Permission::organisatiesBewerken()->toString()]);

        $this->assertCount(2, $users);
        $this->assertEquals($users[0], $userId1);
        $this->assertEquals($users[1], $userId2);
    }
}
