<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20230927114216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');

        $table->dropColumn('parentUuid');
        $table->dropColumn('count_col');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');

        $table->addColumn('parentUuid', Type::GUID)
            ->setLength(36)
            ->setNotnull(false);
        $table->addColumn('count_col', Type::BIGINT)
            ->setNotnull(true)
            ->setDefault(0);
    }
}
