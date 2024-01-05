<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230206084420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('contributor_relations');

        $table->addColumn('uuid', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('email', Types::STRING)->setLength(255)->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('contributor_relations');
    }
}
