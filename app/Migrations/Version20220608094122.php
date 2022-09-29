<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20220608094122 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->addColumn('canonical', Type::GUID)
            ->setLength(36)
            ->setNotnull(false)
            ->setDefault(null);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->dropColumn('canonical');
    }
}
