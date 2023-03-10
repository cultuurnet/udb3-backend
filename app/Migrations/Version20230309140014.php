<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230309140014 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('news_article');
        $table->addColumn('image_url', Type::TEXT)->setDefault(null)->setNotnull(false);
        $table->addColumn('copyright_holder', Type::TEXT)
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
