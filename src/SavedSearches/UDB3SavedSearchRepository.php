<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface as SavedSearchReadModelRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface as SavedSearchWriteModelRepositoryInterface;
use Doctrine\DBAL\Connection;

class UDB3SavedSearchRepository implements SavedSearchReadModelRepositoryInterface, SavedSearchWriteModelRepositoryInterface
{
    private Connection $connection;

    private string $tableName;

    private string $userId;


    public function __construct(
        Connection $connection,
        string $tableName,
        string $userId
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->userId = $userId;
    }

    public function write(
        string $id,
        string $userId,
        string $name,
        QueryString $queryString
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->tableName)
            ->values(
                [
                    SchemaConfigurator::ID => '?',
                    SchemaConfigurator::USER => '?',
                    SchemaConfigurator::NAME => '?',
                    SchemaConfigurator::QUERY => '?',
                ]
            )
            ->setParameters(
                [
                    $id,
                    $userId,
                    $name,
                    $queryString->toString(),
                ]
            );

        $queryBuilder->execute();
    }

    public function delete(
        string $userId,
        string $searchId
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->delete($this->tableName)
            ->where(SchemaConfigurator::USER . ' = ?')
            ->andWhere(SchemaConfigurator::ID . ' = ?')
            ->setParameters(
                [
                    $userId,
                    $searchId,
                ]
            );

        $queryBuilder->execute();
    }

    /**
     * @inheritdoc
     */
    public function ownedByCurrentUser(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where(SchemaConfigurator::USER . ' = ?')
            ->setParameters(
                [
                    $this->userId,
                ]
            );

        $statement = $queryBuilder->execute();

        $savedSearches = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $savedSearches[] = new SavedSearch(
                $row[SchemaConfigurator::NAME],
                new QueryString($row[SchemaConfigurator::QUERY]),
                $row[SchemaConfigurator::ID]
            );
        }


        return $savedSearches;
    }
}
