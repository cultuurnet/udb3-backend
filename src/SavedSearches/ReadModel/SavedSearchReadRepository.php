<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use Doctrine\DBAL\Connection;

class SavedSearchReadRepository
{
    private Connection $connection;

    private string $tableName;

    public function __construct(
        Connection $connection,
        string $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }

    public function findById(string $id): ?SavedSearch
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->andWhere(SchemaConfigurator::ID . ' = ?')
            ->setParameters(
                [
                    $id,
                ]
            );

        $row = $queryBuilder->execute()->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return new SavedSearch(
            $row[SchemaConfigurator::NAME],
            new QueryString($row[SchemaConfigurator::QUERY]),
            $row[SchemaConfigurator::ID],
            $row[SchemaConfigurator::USER],
        );
    }
}
