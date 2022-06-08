<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220608095217 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->addIndex(['place_uuid'], 'place_index');
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('duplicate_places')
            ->dropIndex('place_index');
    }
}
