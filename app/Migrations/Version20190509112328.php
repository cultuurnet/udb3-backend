<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20190509112328 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('roles_search_v3');

        $table->changeColumn(
            'constraint_query',
            [
                'type' => Type::getType(Types::TEXT),
                'length' => MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT + 1,
            ]
        );
    }


    public function down(Schema $schema): void
    {
        $table = $schema->getTable('roles_search_v3');

        $table->changeColumn(
            'constraint_query',
            [
                'type' => Type::getType(Types::STRING),
                'length' => 255,
            ]
        );
    }
}
