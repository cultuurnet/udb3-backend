<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\Migrations\AbstractMigration;

final class Version20230614094123 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('event_permission_readmodel');
        try {
            $table->addIndex(['user_id'], 'user_id_idx');
        } catch (SchemaException $e) {
            if ($e->getCode() !== SchemaException::INDEX_ALREADY_EXISTS) {
                throw $e;
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('event_permission_readmodel');
        $table->dropIndex('user_id_idx');
    }
}
