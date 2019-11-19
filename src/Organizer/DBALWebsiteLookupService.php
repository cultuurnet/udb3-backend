<?php

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use Doctrine\DBAL\Connection;
use ValueObjects\Web\Url;

class DBALWebsiteLookupService implements WebsiteLookupServiceInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @param Connection $connection
     * @param string $tableName
     */
    public function __construct(
        Connection $connection,
        $tableName
    ) {
        $this->connection = $connection;
        $this->tableName = (string) $tableName;
    }

    /**
     * @inheritdoc
     */
    public function lookup(Url $url)
    {
        $expr = $this->connection->getExpressionBuilder();

        $results = $this->connection->createQueryBuilder()
            ->select(UniqueDBALEventStoreDecorator::UUID_COLUMN)
            ->from($this->tableName)
            ->where($expr->eq(UniqueDBALEventStoreDecorator::UNIQUE_COLUMN, ':url'))
            ->setParameter(':url', (string) $url)
            ->execute();

        $uuid = $results->fetchColumn();
        return $uuid ? $uuid : null;
    }
}
