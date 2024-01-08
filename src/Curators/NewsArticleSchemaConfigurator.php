<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

final class NewsArticleSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
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
        $table->addColumn('image_url', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('copyright_holder', Types::TEXT)
            ->setDefault(null)
            ->setNotnull(false)
            ->setLength(250);


        $table->setPrimaryKey(['id']);

        return $table;
    }
}
