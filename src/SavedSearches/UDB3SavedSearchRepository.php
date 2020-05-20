<?php

namespace CultuurNet\UDB3\SavedSearches;

use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface as SavedSearchReadModelRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\WriteModel\SavedSearchRepositoryInterface as SavedSearchWriteModelRepositoryInterface;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class UDB3SavedSearchRepository implements SavedSearchReadModelRepositoryInterface, SavedSearchWriteModelRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $tableName;

    /**
     * @var UuidGeneratorInterface
     */
    private $uuidGenerator;

    /**
     * @var StringLiteral
     */
    private $userId;

    /**
     * @param Connection $connection
     * @param StringLiteral $tableName
     * @param UuidGeneratorInterface $uuidGenerator
     * @param StringLiteral $userId
     */
    public function __construct(
        Connection $connection,
        StringLiteral $tableName,
        UuidGeneratorInterface $uuidGenerator,
        StringLiteral $userId
    ) {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->uuidGenerator = $uuidGenerator;
        $this->userId = $userId;
    }

    /**
     * @inheritdoc
     */
    public function write(
        StringLiteral $userId,
        StringLiteral $name,
        QueryString $queryString
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->insert($this->tableName->toNative())
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
                    $this->uuidGenerator->generate(),
                    $userId->toNative(),
                    $name->toNative(),
                    $queryString->toNative(),
                ]
            );

        $queryBuilder->execute();
    }

    /**
     * @inheritdoc
     */
    public function delete(
        StringLiteral $userId,
        StringLiteral $searchId
    ): void {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->delete($this->tableName->toNative())
            ->where(SchemaConfigurator::USER . ' = ?')
            ->andWhere(SchemaConfigurator::ID . ' = ?')
            ->setParameters(
                [
                    $userId->toNative(),
                    $searchId->toNative(),
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
            ->from($this->tableName->toNative())
            ->where(SchemaConfigurator::USER . ' = ?')
            ->setParameters(
                [
                    $this->userId->toNative(),
                ]
            );

        $statement = $queryBuilder->execute();

        $savedSearches = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $savedSearches[] = new SavedSearch(
                new StringLiteral($row[SchemaConfigurator::NAME]),
                new QueryString($row[SchemaConfigurator::QUERY]),
                new StringLiteral($row[SchemaConfigurator::ID])
            );
        }


        return $savedSearches;
    }
}
