<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a created column to the index read-model.
 */
class Version20160322095452 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $schema
            ->getTable('index_readmodel')
            ->addColumn(
                'updated',
                'text',
                array('length' => 36, 'notnull' => true)
            );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('updated');
    }
}
