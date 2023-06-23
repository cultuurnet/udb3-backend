<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180914083853 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('index_readmodel');

        $table->addColumn('city', Type::STRING)
            ->setLength(256);
    }


    public function down(Schema $schema): void
    {
        $schema->getTable('index_readmodel')
            ->dropColumn('city');
    }
}
