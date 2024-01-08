<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230526075219 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');
        $table->addColumn('excluded', Types::BOOLEAN)
            ->setDefault(false)
            ->setNotnull(true);
        $table->addIndex(['excluded']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');
        $table->dropColumn('excluded');
    }
}
