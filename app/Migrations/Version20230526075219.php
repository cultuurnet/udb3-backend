<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526075219 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');
        $table->addColumn('excluded', Type::BOOLEAN)
            ->setDefault(false)
            ->setNotnull(true);
        $table->addUniqueIndex(['excluded']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('labels_json');
        $table->dropColumn('excluded');
    }
}
