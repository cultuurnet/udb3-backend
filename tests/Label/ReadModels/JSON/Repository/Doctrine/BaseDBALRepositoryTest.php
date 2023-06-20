<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

abstract class BaseDBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private StringLiteral $tableName;

    protected function setUp(): void
    {
        $this->tableName = new StringLiteral('test_places_json');

        $schemaConfigurator = new SchemaConfigurator($this->tableName);

        $schemaManager = $this->getConnection()->getSchemaManager();

        $schemaConfigurator->configure($schemaManager);
    }

    protected function getTableName(): StringLiteral
    {
        return $this->tableName;
    }

    protected function saveEntity(Entity $entity): void
    {
        $values = $this->entityToValues($entity);

        $sql = 'INSERT INTO ' . $this->tableName . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

        $this->connection->executeQuery($sql, $values);
    }

    protected function entityToValues(Entity $entity): array
    {
        return [
            $entity->getUuid()->toString(),
            $entity->getName()->toNative(),
            $entity->getVisibility()->sameAs(Visibility::VISIBLE()),
            $entity->getPrivacy()->sameAs(Privacy::PRIVACY_PRIVATE()),
            null,
            0,
            $entity->isExcluded(),
        ];
    }

    protected function getEntity(): Entity
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $this->rowToEntity($row);
    }

    protected function rowToEntity(array $row): Entity
    {
        return new Entity(
            new UUID($row[SchemaConfigurator::UUID_COLUMN]),
            new StringLiteral($row[SchemaConfigurator::NAME_COLUMN]),
            $row[SchemaConfigurator::VISIBLE_COLUMN]
                ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
            $row[SchemaConfigurator::PRIVATE_COLUMN]
                ? Privacy::PRIVACY_PRIVATE() : Privacy::PRIVACY_PUBLIC(),
            (bool) $row[SchemaConfigurator::EXCLUDED_COLUMN]
        );
    }
}
