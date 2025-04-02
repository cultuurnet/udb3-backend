<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250302072001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('mails_sent');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);

        $table->dropPrimaryKey();
        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('mails_sent');

        $table->dropPrimaryKey();
        $table->dropColumn('id');
        $table->setPrimaryKey(['identifier', 'type']);
    }
}
