<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20240902071940 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // change int -> string for cluster id
        $table = $schema->getTable('duplicate_places');
        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::STRING),
                'length' => 40,
            ]
        );
        $table->setPrimaryKey(['cluster_id', 'place_uuid']);
        $table->addIndex(['canonical'], 'canonical');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('duplicate_places');
        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::BIGINT),
            ]
        );
        $table->dropPrimaryKey();
        $table->dropIndex('canonical');
    }
}
