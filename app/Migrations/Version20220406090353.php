<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

final class Version20220406090353 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $schema->getTable('duplicate_places')
            ->dropColumn('is_canonical');
    }

    public function down(Schema $schema) : void
    {
        $schema->getTable('duplicate_places')
            ->addColumn('is_canonical', Type::BOOLEAN)
            ->setNotnull(true);
    }
}
