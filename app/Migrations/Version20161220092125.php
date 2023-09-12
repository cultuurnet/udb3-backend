<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

final class Version20161220092125 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('events');
        $this->removeEventsByType($table, 'CultuurNet.UDB3.UsedLabelsMemory.Created');
        $this->removeEventsByType($table, 'CultuurNet.UDB3.UsedLabelsMemory.LabelUsed');
    }


    public function down(Schema $schema): void
    {
    }


    private function removeEventsByType(Table $table, string $eventType): void
    {
        $builder = new QueryBuilder($this->connection);

        $builder
            ->delete($table->getName())
            ->where('type = ?')
            ->setParameter(0, $eventType)
            ->execute();
    }
}
