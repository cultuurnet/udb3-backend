<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20211109142612 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('event_recommendations')->dropPrimaryKey();
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('event_recommendations')->setPrimaryKey(['event_id']);
    }
}
