<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

class PopularitySchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable('offer_popularity');

        $table->addColumn(
            'offer_id',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'offer_type',
            'string',
            [
                'length' => 32,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'popularity',
            'bigint',
            [
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'creation_date',
            'datetime',
            [
                'notnull' => true,
            ]
        );

        $table->setPrimaryKey(
            [
                'offer_id',
            ],
            'offer_id_index'
        );

        return $table;
    }
}
