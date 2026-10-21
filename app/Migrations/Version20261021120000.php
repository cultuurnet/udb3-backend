<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261021120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('ticketing_registrations');

        $table->addColumn('udb_event_id', Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        // 512 keeps the primary key within the MySQL index size limit.
        $table->addColumn('reservation_link', Types::STRING)
            ->setLength(512)
            ->setNotnull(true);

        $table->addColumn('source', Types::STRING)
            ->setLength(16)
            ->setNotnull(true);

        $table->addColumn('identifier', Types::STRING)
            ->setLength(255)
            ->setNotnull(false)
            ->setDefault(null);

        $table->setPrimaryKey(['udb_event_id', 'reservation_link', 'source']);
        $table->addIndex(['identifier'], 'ticketing_registrations_identifier');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('ticketing_registrations');
    }
}
