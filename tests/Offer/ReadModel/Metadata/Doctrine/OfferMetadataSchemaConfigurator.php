<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata\Doctrine;

use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class OfferMetadataSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable(OfferMetadataRepository::TABLE);

        $table->addColumn('id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('created_by_api_consumer', Types::STRING)->setLength(255)->setNotnull(true);
        $table->setPrimaryKey(['id']);

        return $table;
    }
}
