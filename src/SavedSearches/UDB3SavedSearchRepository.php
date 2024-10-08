<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Doctrine\ColumnNames;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser as SavedSearchReadModelRepositoryInterface;
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

    public function insert(
        string $id,
        string $userId,
        string $name,
        QueryString $queryString
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->tableName)
            ->values(
                [
                    ColumnNames::ID => '?',
                    ColumnNames::USER => '?',
                    ColumnNames::NAME => '?',
                    ColumnNames::QUERY => '?',
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

    public function update(
        string $id,
        string $userId,
        string $name,
        QueryString $queryString
    ): void {
        $this->connection->createQueryBuilder()
            ->update($this->tableName)
            ->set(ColumnNames::NAME, '?')
            ->set(ColumnNames::QUERY, '?')
            ->where(ColumnNames::USER . ' = ?')
            ->andWhere(ColumnNames::ID . ' = ?')
            ->setParameters(
                [
                    $name,
                    $queryString->toString(),
                    $userId,
                    $id,
                ]
            )
            ->execute();
    }

    public function delete(
        string $userId,
        string $searchId
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->delete($this->tableName)
            ->where(ColumnNames::USER . ' = ?')
            ->andWhere(ColumnNames::ID . ' = ?')
            ->setParameters(
                [
                    $userId,
                    $searchId,
                ]
            );

        $queryBuilder->execute();
    }

    public function ownedByCurrentUser(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where(ColumnNames::USER . ' = ?')
            ->setParameters(
                [
                    $this->userId,
                ]
            );

        $statement = $queryBuilder->execute();

        $savedSearches = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $savedSearches[] = new SavedSearch(
                $row[ColumnNames::NAME],
                new QueryString($row[ColumnNames::QUERY]),
                $row[ColumnNames::ID],
                $row[ColumnNames::USER]
            );
        }


        return $savedSearches;
    }
}
