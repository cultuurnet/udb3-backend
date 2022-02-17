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

    /**
     * @var StringLiteral
     */
    private $tableName;

    protected function setUp()
    {
        $this->tableName = new StringLiteral('test_places_json');

        $schemaConfigurator = new SchemaConfigurator($this->tableName);

        $schemaManager = $this->getConnection()->getSchemaManager();

        $schemaConfigurator->configure($schemaManager);
    }

    /**
     * @return StringLiteral
     */
    protected function getTableName()
    {
        return $this->tableName;
    }


    protected function saveEntity(Entity $entity)
    {
        $values = $this->entityToValues($entity);

        $sql = 'INSERT INTO ' . $this->tableName . ' VALUES (?, ?, ?, ?, ?, ?)';

        $this->connection->executeQuery($sql, $values);
    }

    /**
     * @return array
     */
    protected function entityToValues(Entity $entity)
    {
        return [
            $entity->getUuid()->toString(),
            $entity->getName()->toNative(),
            $entity->getVisibility()->sameAs(Visibility::VISIBLE()),
            $entity->getPrivacy()->sameAs(Privacy::PRIVACY_PRIVATE()),
            $entity->getParentUuid()->toString(),
            $entity->getCount(),
        ];
    }

    /**
     * @return Entity
     */
    protected function getEntity()
    {
        $sql = 'SELECT * FROM ' . $this->tableName;

        $statement = $this->connection->executeQuery($sql);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $this->rowToEntity($row);
    }

    /**
     * @return Entity
     */
    protected function rowToEntity(array $row)
    {
        return new Entity(
            new UUID($row[SchemaConfigurator::UUID_COLUMN]),
            new StringLiteral($row[SchemaConfigurator::NAME_COLUMN]),
            $row[SchemaConfigurator::VISIBLE_COLUMN]
                ? Visibility::VISIBLE() : Visibility::INVISIBLE(),
            $row[SchemaConfigurator::PRIVATE_COLUMN]
                ? Privacy::PRIVACY_PRIVATE() : Privacy::PRIVACY_PUBLIC(),
            new UUID($row[SchemaConfigurator::PARENT_UUID_COLUMN]),
            (int) $row[SchemaConfigurator::COUNT_COLUMN]
        );
    }
}
