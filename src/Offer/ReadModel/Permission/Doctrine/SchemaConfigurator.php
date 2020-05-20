<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use ValueObjects\StringLiteral\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    /**
     * @var StringLiteral
     */
    protected $tableName;

    /**
     * @var StringLiteral
     */
    protected $idField;

    /**
     * @param StringLiteral $tableName
     * @param StringLiteral $idField
     */
    public function __construct(StringLiteral $tableName, StringLiteral $idField)
    {
        $this->tableName = $tableName;
        $this->idField = $idField;
    }

    /**
     * @inheritdoc
     */
    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();
        $table = $schema->createTable($this->tableName->toNative());

        $table->addColumn(
            $this->idField->toNative(),
            'guid',
            array('length' => 36, 'notnull' => true)
        );
        $table->addColumn(
            'user_id',
            'guid',
            array('length' => 36, 'notnull' => true)
        );

        $table->setPrimaryKey([$this->idField->toNative(), 'user_id']);

        $schemaManager->createTable($table);
    }
}
