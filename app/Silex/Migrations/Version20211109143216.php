<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20211109143216 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('event_recommendations')->addIndex(['event_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('event_recommendations')->dropIndex('event_id');
    }
}
