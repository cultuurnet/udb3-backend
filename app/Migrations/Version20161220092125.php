<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161220092125 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('events');
        $this->removeEventsByType($table, new StringLiteral('CultuurNet.UDB3.UsedLabelsMemory.Created'));
        $this->removeEventsByType($table, new StringLiteral('CultuurNet.UDB3.UsedLabelsMemory.LabelUsed'));
    }


    public function down(Schema $schema)
    {
    }


    private function removeEventsByType(Table $table, StringLiteral $eventType)
    {
        $builder = new QueryBuilder($this->connection);

        $builder
            ->delete($table->getName())
            ->where('type = ?')
            ->setParameter(0, (string) $eventType)
            ->execute();
    }
}
