<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200909165220 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('similar_events')
            ->changeColumn('similarity', ['scale' => 2]);
    }


    public function down(Schema $schema): void
    {
        $schema->getTable('similar_events')
            ->changeColumn('similarity', ['scale' => 0]);
    }
}
