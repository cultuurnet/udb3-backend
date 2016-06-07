<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160607134416 extends AbstractMigration
{
    const LABELS_JSON_TABLE = 'labels_json';
    const LABELS_RELATIONS_TABLE = 'labels_relations';

    const UUID_COLUMN = 'uuid_col';
    const NAME_COLUMN = 'name';
    const VISIBLE_COLUMN = 'visible';
    const PRIVATE_COLUMN = 'private';
    const PARENT_UUID_COLUMN = 'parentUuid';
    const COUNT_COLUMN = 'count_col';

    const RELATION_TYPE_COLUMN = 'relationType';
    const RELATION_ID_COLUMN = 'relationId';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createJsonRepository($schema);

        $this->createRelationsRepository($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::LABELS_JSON_TABLE);

        $schema->dropTable(self::LABELS_RELATIONS_TABLE);
    }

    /**
     * @param Schema $schema
     */
    private function createJsonRepository(Schema $schema)
    {
        $table = $schema->createTable(self::LABELS_JSON_TABLE);

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::NAME_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::VISIBLE_COLUMN, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(true);

        $table->addColumn(self::PRIVATE_COLUMN, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->addColumn(self::PARENT_UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(false);

        $table->addColumn(self::COUNT_COLUMN, Type::BIGINT)
            ->setNotnull(true)
            ->setDefault(0);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::NAME_COLUMN]);
    }

    /**
     * @param Schema $schema
     */
    private function createRelationsRepository(Schema $schema)
    {
        $table = $schema->createTable(self::LABELS_RELATIONS_TABLE);

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::RELATION_TYPE_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::RELATION_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex(
            [
                self::UUID_COLUMN,
                self::RELATION_TYPE_COLUMN,
                self::RELATION_ID_COLUMN,
            ]
        );
    }
}
