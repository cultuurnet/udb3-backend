<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200618120205 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table =  $schema->getTable('productions');
        // This is the previously added index on 'name' which we no longer need
        $table->dropIndex('idx_bbd7c0c25e237e06');
        $table->addIndex(['name'], 'idx_search_name', ['fulltext']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('productions')->dropIndex('name');
    }
}
