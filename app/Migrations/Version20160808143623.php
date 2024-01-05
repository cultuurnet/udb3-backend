<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20160808143623 extends AbstractMigration
{
    public const USER_ID_COLUMN = 'user_id';
    public const ROLE_ID_COLUMN = 'role_id';
    public const PERMISSION_COLUMN = 'permission';


    public function up(Schema $schema): void
    {
        $userRoleTable = $schema->createTable(UserPermissionsServiceProvider::USER_ROLES_TABLE);

        $userRoleTable->addColumn(self::USER_ID_COLUMN, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->setPrimaryKey([self::USER_ID_COLUMN, self::ROLE_ID_COLUMN]);


        $rolePermissionTable = $schema->createTable(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE);

        $rolePermissionTable->addColumn(self::ROLE_ID_COLUMN, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $rolePermissionTable->addColumn(self::PERMISSION_COLUMN, Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $rolePermissionTable->setPrimaryKey([self::ROLE_ID_COLUMN, self::PERMISSION_COLUMN]);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(UserPermissionsServiceProvider::USER_ROLES_TABLE);

        $schema->dropTable(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE);
    }
}
