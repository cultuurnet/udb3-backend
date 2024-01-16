<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20230309140014 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('news_article');
        $table->addColumn('image_url', Types::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('copyright_holder', Types::TEXT)
            ->setDefault(null)
            ->setNotnull(false)
            ->setLength(250);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('news_article');
        $table->dropColumn('image_url');
        $table->dropColumn('copyright_holder');
    }
}
