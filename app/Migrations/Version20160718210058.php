<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160718210058 extends AbstractMigration
{
    public const ROLES_SEARCH = 'roles_search';


    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::ROLES_SEARCH);

        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('name', 'string')->setLength(255);

        $table->setPrimaryKey(['uuid']);
        $table->addUniqueIndex(['uuid', 'name']);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable(self::ROLES_SEARCH);
    }
}
