<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStore;
use CultuurNet\UDB3\EventSourcing\AbstractEventStoreDecorator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class UniqueDBALEventStoreDecorator extends AbstractEventStoreDecorator
{
    public const UUID_COLUMN = 'uuid_col';
    public const UNIQUE_COLUMN = 'unique_col';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $uniqueTableName;

    /**
     * @var UniqueConstraintService
     */
    private $uniqueConstraintService;

    public function __construct(
        EventStore $dbalEventStore,
        Connection $connection,
        string $uniqueTableName,
        UniqueConstraintService $uniqueConstraintService
    ) {
        parent::__construct($dbalEventStore);

        $this->connection = $connection;
        $this->uniqueTableName = $uniqueTableName;
        $this->uniqueConstraintService = $uniqueConstraintService;
    }

    /**
     * @inheritdoc
     * @throws UniqueConstraintException
     */
    public function append($id, DomainEventStream $eventStream)
    {
        $this->connection->beginTransaction();

        try {
            // First make sure that the events itself can be stored,
            // then check the uniqueness.
            parent::append($id, $eventStream);

            foreach ($eventStream as $domainMessage) {
                $this->processUniqueConstraint($domainMessage);
            }

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function configureSchema(Schema $schema): ?Table
    {
        if ($schema->hasTable($this->uniqueTableName)) {
            return null;
        }

        return $this->createUniqueTable($this->uniqueTableName);
    }

    private function createUniqueTable(string $tableName): Table
    {
        $schema = new Schema();

        $table = $schema->createTable($tableName);

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::UNIQUE_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UNIQUE_COLUMN]);

        return $table;
    }

    /**
     * @throws UniqueConstraintException
     */
    private function processUniqueConstraint(
        DomainMessage $domainMessage
    ) {
        if ($this->uniqueConstraintService->hasUniqueConstraint($domainMessage)) {
            $uniqueValue = $this->uniqueConstraintService->getUniqueConstraintValue($domainMessage);

            if ($this->uniqueConstraintService->needsPreflightLookup()) {
                $this->executePreflightLookup($uniqueValue, $domainMessage->getId());
            }

            try {
                $this->connection->insert(
                    $this->uniqueTableName,
                    [
                        self::UUID_COLUMN => $domainMessage->getId(),
                        self::UNIQUE_COLUMN => $uniqueValue,
                    ]
                );
            } catch (UniqueConstraintViolationException $e) {
                if ($this->uniqueConstraintService->needsUpdateUniqueConstraint($domainMessage)) {
                    $this->updateUniqueConstraint(
                        $domainMessage->getId(),
                        $uniqueValue
                    );
                } else {
                    throw new UniqueConstraintException(
                        $domainMessage->getId(),
                        $uniqueValue
                    );
                }
            }
        }
    }

    private function updateUniqueConstraint(string $id, string $uniqueValue): void
    {
        try {
            $this->connection->update(
                $this->uniqueTableName,
                [
                    self::UNIQUE_COLUMN => $uniqueValue,
                ],
                [
                    self::UUID_COLUMN => $id,
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            throw new UniqueConstraintException(
                $id,
                $uniqueValue
            );
        }
    }

    private function executePreflightLookup(string $uniqueValue, string $domainMessageId): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $likeUniqueValue = $queryBuilder->expr()->like(
            self::UNIQUE_COLUMN,
            ':uniqueValue'
        );

        $rows = $queryBuilder->select(self::UUID_COLUMN)
            ->from($this->uniqueTableName)
            ->where($likeUniqueValue)
            ->setParameter('uniqueValue', $uniqueValue)
            ->execute()
            ->fetchAll();

        if (!empty($rows)) {
            throw new UniqueConstraintException(
                $domainMessageId,
                $uniqueValue
            );
        }
    }
}
