<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainEventStreamInterface;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\EventSourcing\AbstractEventStoreDecorator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use ValueObjects\StringLiteral\StringLiteral;

class UniqueDBALEventStoreDecorator extends AbstractEventStoreDecorator
{
    const UUID_COLUMN = 'uuid_col';
    const UNIQUE_COLUMN = 'unique_col';

    /**
     * @var EventStoreInterface
     */
    private $dbalEventStore;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $uniqueTableName;

    /**
     * @var UniqueConstraintServiceInterface
     */
    private $uniqueConstraintService;

    /**
     * UniqueNameDBALEventStoreDecorator constructor.
     * @param EventStoreInterface $dbalEventStore
     * @param Connection $connection
     * @param StringLiteral $uniqueTableName
     * @param UniqueConstraintServiceInterface $uniqueConstraintService
     */
    public function __construct(
        EventStoreInterface $dbalEventStore,
        Connection $connection,
        StringLiteral $uniqueTableName,
        UniqueConstraintServiceInterface $uniqueConstraintService
    ) {
        parent::__construct($dbalEventStore);

        $this->dbalEventStore = $dbalEventStore;
        $this->connection = $connection;
        $this->uniqueTableName = $uniqueTableName;
        $this->uniqueConstraintService = $uniqueConstraintService;
    }

    /**
     * @inheritdoc
     * @throws UniqueConstraintException
     */
    public function append($id, DomainEventStreamInterface $eventStream)
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

    /**
     * @inheritdoc
     */
    public function configureSchema(Schema $schema)
    {
        if ($schema->hasTable($this->uniqueTableName->toNative())) {
            return null;
        }

        return $this->createUniqueTable($this->uniqueTableName);
    }

    /**
     * @param StringLiteral $tableName
     * @return Table
     */
    private function createUniqueTable(
        StringLiteral $tableName
    ) {
        $schema = new Schema();

        $table = $schema->createTable($tableName->toNative());

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
     * @param DomainMessage $domainMessage
     * @throws UniqueConstraintException
     */
    private function processUniqueConstraint(
        DomainMessage $domainMessage
    ) {
        if ($this->uniqueConstraintService->hasUniqueConstraint($domainMessage)) {
            $uniqueValue = $this->uniqueConstraintService->getUniqueConstraintValue($domainMessage);

            try {
                $this->connection->insert(
                    $this->uniqueTableName,
                    [
                        self::UUID_COLUMN => $domainMessage->getId(),
                        self::UNIQUE_COLUMN => $uniqueValue->toNative(),
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

    /**
     * @param string $id
     * @param StringLiteral $uniqueValue
     * @throws UniqueConstraintException
     */
    private function updateUniqueConstraint(
        $id,
        StringLiteral $uniqueValue
    ) {
        try {
            $this->connection->update(
                $this->uniqueTableName,
                [
                    self::UNIQUE_COLUMN => $uniqueValue->toNative(),
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
}
