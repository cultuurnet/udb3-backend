<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class Version20160808143623 extends AbstractMigration
{
    public const USER_ID_COLUMN = 'user_id';
    public const ROLE_ID_COLUMN = 'role_id';
    public const PERMISSION_COLUMN = 'permission';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $userRoleTable = $schema->createTable(UserPermissionsServiceProvider::USER_ROLES_TABLE);

        $userRoleTable->addColumn(self::USER_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->setPrimaryKey([self::USER_ID_COLUMN, self::ROLE_ID_COLUMN]);


        $rolePermissionTable = $schema->createTable(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE);

        $rolePermissionTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $rolePermissionTable->addColumn(self::PERMISSION_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $rolePermissionTable->setPrimaryKey([self::ROLE_ID_COLUMN, self::PERMISSION_COLUMN]);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(UserPermissionsServiceProvider::USER_ROLES_TABLE);

        $schema->dropTable(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE);
    }
}
