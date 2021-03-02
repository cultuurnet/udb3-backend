<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200909165220 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $schema->getTable('similar_events')
            ->changeColumn('similarity', ['scale' => 2]);
    }


    public function down(Schema $schema)
    {
        $schema->getTable('similar_events')
            ->changeColumn('similarity', ['scale' => 0]);
    }
}
