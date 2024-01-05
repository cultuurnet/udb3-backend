<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use CultuurNet\UDB3\Labels\LabelServiceProvider;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

class Version20160817112023 extends AbstractMigration
{
    public const LABEL_ID_COLUMN = 'label_id';
    public const ROLE_ID_COLUMN = 'role_id';


    public function up(Schema $schema): void
    {
        $userRoleTable = $schema->createTable(
            LabelServiceProvider::LABEL_ROLES_TABLE
        );

        $userRoleTable->addColumn(self::LABEL_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->setPrimaryKey(
            [
                self::LABEL_ID_COLUMN,
                self::ROLE_ID_COLUMN,
            ]
        );
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(LabelServiceProvider::LABEL_ROLES_TABLE);
    }
}
