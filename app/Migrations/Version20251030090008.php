<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20251030090008 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');

        $table->addColumn('created', Types::STRING)
            ->setLength(32)
            ->setNotnull(false)
            ->setDefault(null);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');
        $table->dropColumn('created');
    }
}
