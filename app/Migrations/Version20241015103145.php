<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241015103145 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');

        $table->addColumn('role_id', Types::GUID)
            ->setLength(36)
            ->setNotnull(false)
            ->setDefault(null);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('ownership_search');

        $table->dropColumn('role_id');
    }
}
