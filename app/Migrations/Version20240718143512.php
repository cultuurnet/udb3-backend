<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240718143512 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('duplicate_places');

        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::STRING),
                'length' => 40, //SHA1 length
            ]
        );

        $table->addColumn('created_date', Types::DATETIME_IMMUTABLE);

        $table->addColumn(
            'processed',
            Types::BOOLEAN,
            ['default' => false]
        )
        ->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('duplicate_places');

        $table->dropColumn('created_date');
        $table->dropColumn('processed');

        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::BIGINT),
                'length' => 40, //SHA1 length
            ]
        );
    }
}
