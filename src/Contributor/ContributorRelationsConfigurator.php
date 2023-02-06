<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

final class ContributorRelationsConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable('contributor_relations');

        $table->addColumn('uuid', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('email', Type::TEXT)->setNotnull(true);

        return $table;
    }
}
