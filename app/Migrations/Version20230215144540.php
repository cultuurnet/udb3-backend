<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230215144540 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('contributor_relations');
        $table->addColumn('type', Types::STRING)->setLength(255)->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('contributor_relations');
        $table->dropColumn('type');
    }
}
