<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20170731151814 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->getIndexTable($schema)
            ->changeColumn(
                'zip',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 32,
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'created',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 32,
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'updated',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 32,
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'owning_domain',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 256,
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'entity_iri',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 256,
                ]
            );
    }


    public function down(Schema $schema): void
    {
        $this->getIndexTable($schema)
            ->dropPrimaryKey();

        $this->getIndexTable($schema)
            ->changeColumn(
                'zip',
                [
                    'type' => Type::getType(Types::TEXT),
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'created',
                [
                    'type' => Type::getType(Types::TEXT),
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'updated',
                [
                    'type' => Type::getType(Types::TEXT),
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'owning_domain',
                [
                    'type' => Type::getType(Types::TEXT),
                ]
            );

        $this->getIndexTable($schema)
            ->changeColumn(
                'entity_iri',
                [
                    'type' => Type::getType(Types::TEXT),
                ]
            );
    }

    private function getIndexTable(Schema $schema): Table
    {
        return $schema->getTable('index_readmodel');
    }
}
