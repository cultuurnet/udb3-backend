<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160322095452 extends AbstractMigration
{
    public function up(Schema $schema): void
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


    public function down(Schema $schema): void
    {
        $schema
            ->getTable('index_readmodel')
            ->dropColumn('updated')
            ->dropColumn('owning_domain')
            ->dropColumn('entity_iri');
    }
}
