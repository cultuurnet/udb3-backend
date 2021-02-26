<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a created column to the index read-model.
 */
class Version20160322095452 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('index_readmodel');

        $table->addColumn(
            'updated',
            'text',
            ['length' => 36, 'notnull' => true]
        );

        $table->addColumn(
            'owning_domain',
            'text',
            ['length' => 36, 'notnull' => true]
        );

        $table->addColumn(
            'entity_iri',
            'text'
        );
    }


    public function down(Schema $schema)
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('updated')
            ->dropColumn('owning_domain')
            ->dropColumn('entity_iri');
    }
}
