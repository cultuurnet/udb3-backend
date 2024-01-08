<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20220406090353 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->dropColumn('is_canonical');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->addColumn('is_canonical', Types::BOOLEAN)
            ->setNotnull(true);
    }
}
