<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241031085217 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('duplicate_places_removed_from_cluster')
            ->dropColumn('cluster_id');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('duplicate_places_removed_from_cluster')
            ->addColumn('cluster_id', Types::STRING)->setNotnull(true)->setLength(40);
    }
}
