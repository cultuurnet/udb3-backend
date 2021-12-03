<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

final class NewsArticleSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
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

        return $table;
    }
}
