<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20211201160612 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('news_article');

        $table->addColumn('id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('headline', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('in_language', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('text', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('about', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('publisher', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('url', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('publisher_logo', Types::TEXT)->setDefault(null)->setNotnull(false);

        $table->setPrimaryKey(['id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('news_article');
    }
}
