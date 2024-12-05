<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241205150800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->getTable('event_place_history')
            ->changeColumn(
                'date',
                [
                    'type' => Type::getType(Types::STRING),
                    'length' => 32,
                ]
            );
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('event_place_history')
            ->changeColumn(
                'date',
                [
                    'type' => Type::getType(Types::DATETIME_IMMUTABLE),
                    'length' => 32,
                ]
            );
    }
}
