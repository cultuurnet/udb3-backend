<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240415143512 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');

        $table->addColumn('state', Types::STRING)
            ->setNotnull(true)
            ->setDefault('requested');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');

        $table->dropColumn('state');
    }
}
