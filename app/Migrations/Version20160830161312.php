<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160830161312 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('roles_search');

        $table->addColumn(SchemaConfigurator::CONSTRAINT_COLUMN, Type::STRING)
            ->setNotnull(false);
    }


    public function down(Schema $schema)
    {
        $table = $schema->getTable('roles_search');

        $table->dropColumn(SchemaConfigurator::CONSTRAINT_COLUMN);
    }
}
