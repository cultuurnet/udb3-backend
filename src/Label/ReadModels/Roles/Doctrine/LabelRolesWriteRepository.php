<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Roles\LabelRolesWriteRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class LabelRolesWriteRepository implements LabelRolesWriteRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $labelRolesTableName;

    /**
     * LabelRolesWriteRepository constructor.
     */
    public function __construct(
        Connection $connection,
        StringLiteral $labelRolesTableName
    ) {
        $this->connection = $connection;
        $this->labelRolesTableName = $labelRolesTableName;
    }


    public function insertLabelRole(UUID $labelId, UUID $roleId)
    {
        $this->connection->insert(
            $this->labelRolesTableName,
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId->toString(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }


    public function removeLabelRole(UUID $labelId, UUID $roleId)
    {
        $this->connection->delete(
            $this->labelRolesTableName,
            [
                SchemaConfigurator::LABEL_ID_COLUMN => $labelId->toString(),
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }


    public function removeRole(UUID $roleId)
    {
        $this->connection->delete(
            $this->labelRolesTableName,
            [
                SchemaConfigurator::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }
}
