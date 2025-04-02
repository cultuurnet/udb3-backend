<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20250302072000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');
        $table->addColumn('approved_by', 'string', ['notnull' => false])->setLength(100);
        $table->addColumn('rejected_by', 'string', ['notnull' => false])->setLength(100);
        $table->addColumn('deleted_by', 'string',  ['notnull' => false])->setLength(100);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');
        $table->dropColumn('approved_by');
        $table->dropColumn('rejected_by');
        $table->dropColumn('deleted_by');
    }
}
