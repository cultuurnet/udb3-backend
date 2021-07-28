<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use ValueObjects\StringLiteral\StringLiteral;

class DBALResourceOwnerRepository implements ResourceOwnerRepository, ResourceOwnerQuery
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StringLiteral
     */
    protected $idField;

    /**
     * @var StringLiteral
     */
    protected $tableName;

    /**
     * @param StringLiteral $tableName
     *  The name of the table where the permissions are stored.
     *
     * @param Connection $connection
     *  A database connection.
     *
     * @param StringLiteral $idField
     *  The name of the column that holds the offer identifier.
     *
     */
    public function __construct(
        StringLiteral $tableName,
        Connection $connection,
        StringLiteral $idField
    ) {
        $this->tableName = $tableName;
        $this->connection = $connection;
        $this->idField = $idField;
    }

    /**
     * @inheritdoc
     */
    public function getEditableResourceIds(StringLiteral $userId)
    {
        $q = $this->connection->createQueryBuilder();
        $q->select($this->idField->toNative())
            ->from($this->tableName->toNative())
            ->where('user_id = :userId')
            ->setParameter(':userId', $userId->toNative());

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] = new StringLiteral($id);
        }

        return $events;
    }

    public function markResourceEditableByUser(StringLiteral $eventId, StringLiteral $userId): void
    {
        try {
            $this->connection->insert(
                $this->tableName->toNative(),
                [
                    $this->idField->toNative() => $eventId->toNative(),
                    'user_id' => $userId->toNative(),
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            // Intentionally catching database exception occurring when the
            // permission record is already in place.
        }
    }

    public function markResourceEditableByNewUser(StringLiteral $eventId, StringLiteral $userId): void
    {
        $this->connection->update(
            $this->tableName->toNative(),
            ['user_id' => $userId->toNative()],
            [$this->idField->toNative() => $eventId->toNative()]
        );
    }
}
