<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230614094817 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('contributor_relations');
        $table->addIndex(['email'], 'email_idx');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('contributor_relations');
        $table->dropIndex('email_idx');
    }
}
