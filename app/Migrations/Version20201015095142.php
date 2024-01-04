<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201015095142 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('event_variation_search_index');
    }

    public function down(Schema $schema): void
    {
    }
}
