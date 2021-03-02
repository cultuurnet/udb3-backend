<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210302114100 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema
            ->getTable('labels_import')
            ->dropPrimaryKey();
    }

    public function down(Schema $schema): void
    {
        $schema
            ->getTable('labels_import')
            ->setPrimaryKey(['offer_id'], 'offer_id_index');
    }
}
