<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesWriteRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Connection;

class LabelRolesWriteRepository implements LabelRolesWriteRepositoryInterface
{
    private Connection $connection;

    private string $labelRolesTableName;

    public function __construct(
        Connection $connection,
        string $labelRolesTableName
    ) {
        $this->connection = $connection;
        $this->labelRolesTableName = $labelRolesTableName;
    }

    public function insertLabelRole(UUID $labelId, UUID $roleId): void
    {
        $this->connection->insert(
            $this->labelRolesTableName,
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }

    public function removeLabelRole(UUID $labelId, UUID $roleId): void
    {
        $this->connection->delete(
            $this->labelRolesTableName,
            [
                ColumnNames::LABEL_ID_COLUMN => $labelId->toString(),
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }

    public function removeRole(UUID $roleId): void
    {
        $this->connection->delete(
            $this->labelRolesTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }
}
