<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190509112328 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('roles_search_v3');

        $table->changeColumn(
            'constraint_query',
            [
                'type' => Type::getType(Type::TEXT),
                'length' => MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT + 1,
            ]
        );
    }


    public function down(Schema $schema)
    {
        $table = $schema->getTable('roles_search_v3');

        $table->changeColumn(
            'constraint_query',
            [
                'type' => Type::getType(Type::STRING),
                'length' => 255,
            ]
        );
    }
}
