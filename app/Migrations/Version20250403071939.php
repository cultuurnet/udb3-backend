<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20250403071939 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('mails_sent');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->createTable('mails_sent');

        $table->addColumn('identifier', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('email', Types::STRING)->setLength(320)->setNotnull(true);
        $table->addColumn('type', Types::STRING)->setLength(100)->setNotnull(true);
        $table->addColumn('dateTime', Types::DATETIME_IMMUTABLE)->setNotnull(true);

        $table->setPrimaryKey(['identifier', 'type']);
    }
}
