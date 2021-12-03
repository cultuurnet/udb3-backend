<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

class Version20211201160612 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('news_article');

        $table->addColumn('id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('headline', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('in_language', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('text', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('about', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('publisher', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('url', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('publisher_logo', Type::TEXT)->setDefault(null)->setNotnull(false);

        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('news_article');
    }
}
