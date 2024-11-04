<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241031085647 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('duplicate_places_import')
            ->dropColumn('canonical');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('duplicate_places_import')
            ->addColumn('canonical', Types::GUID)->setLength(36)->setNotnull(false)->setDefault(null);
    }
}
