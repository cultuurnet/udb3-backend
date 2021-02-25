<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160607132920 extends AbstractMigration
{
    public const LABELS_TABLE = 'labels';
    public const LABELS_UNIQUE_TABLE = 'labels_unique';

    public const UUID_COLUMN = 'uuid_col';
    public const UNIQUE_COLUMN = 'unique_col';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createLabelStore($schema);

        $this->createUniqueLabelStore($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::LABELS_TABLE);

        $schema->dropTable(self::LABELS_UNIQUE_TABLE);
    }

    /**
     * @param Schema $schema
     */
    private function createLabelStore(Schema $schema)
    {
        $table = $schema->createTable(self::LABELS_TABLE);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('playhead', 'integer', ['unsigned' => true]);
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', ['length' => 32]);
        $table->addColumn('type', 'text');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['uuid', 'playhead']);
    }

    /**
     * @param Schema $schema
     */
    private function createUniqueLabelStore(Schema $schema)
    {
        $table = $schema->createTable(self::LABELS_UNIQUE_TABLE);

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::UNIQUE_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UNIQUE_COLUMN]);
    }
}
