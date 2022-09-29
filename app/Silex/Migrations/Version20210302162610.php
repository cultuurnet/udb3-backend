<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210302162610 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema
            ->getTable('labels_import')
            ->dropColumn('offer_type');
    }

    public function down(Schema $schema): void
    {
        $schema
            ->getTable('labels_import')
            ->addColumn(
                'offer_type',
                'string',
                [
                    'length' => 32,
                    'notnull' => true,
                ]
            );
    }
}
