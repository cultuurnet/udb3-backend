<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PDO;
use PHPUnit\Framework\TestCase;

abstract class BaseDBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private string $tableName;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->tableName = 'labels_json';
    }

    protected function getTableName(): string
    {
        return $this->tableName;
    }

    protected function saveEntity(Entity $entity): void
    {
        $values = $this->entityToValues($entity);

        $sql = 'INSERT INTO ' . $this->tableName . ' VALUES (?, ?, ?, ?, ?)';

        $this->connection->executeQuery($sql, $values, [
            PDO::PARAM_STR,
            PDO::PARAM_STR,
            PDO::PARAM_BOOL,
            PDO::PARAM_BOOL,
            PDO::PARAM_BOOL,
        ]);
    }

    protected function entityToValues(Entity $entity): array
    {
        return [
            $entity->getUuid()->toString(),
            $entity->getName(),
            $entity->getVisibility()->sameAs(Visibility::VISIBLE()),
            $entity->getPrivacy()->sameAs(Privacy::PRIVACY_PRIVATE()),
            $entity->isExcluded(),
        ];
    }

    protected function getEntity(): Entity
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $this->rowToEntity($row);
    }

    protected function rowToEntity(array $row): Entity
    {
        return new Entity(
            new Uuid($row[ColumnNames::UUID_COLUMN]),
            $row[ColumnNames::NAME_COLUMN],
            $row[ColumnNames::VISIBLE_COLUMN]
                ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
            $row[ColumnNames::PRIVATE_COLUMN]
                ? Privacy::PRIVACY_PRIVATE() : Privacy::PRIVACY_PUBLIC(),
            (bool) $row[ColumnNames::EXCLUDED_COLUMN]
        );
    }
}
