<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the multi-purpose index read model.
 */
class Version20150817113039 extends AbstractMigration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('index_readmodel');

        $table->addColumn(
            'entity_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'entity_type',
            'string',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'title',
            'text'
        );
        $table->addColumn(
            'uid',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'zip',
            'text'
        );
        $table->addColumn(
            'created',
            'text',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey(['entity_id', 'entity_type']);
    }

    /**
     * @inheritdoc
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('index_readmodel');
    }
}
