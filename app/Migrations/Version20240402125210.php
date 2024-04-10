<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240402125210 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('kinepolis_movie_mapping');

        $table->addColumn('event_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('movie_id', Types::STRING)->setNotnull(true);

        $table->setPrimaryKey(['event_id']);
        $table->addIndex(['movie_id']);
        $table->addUniqueIndex(['movie_id', 'event_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('kinepolis_movie_mapping');
    }
}
