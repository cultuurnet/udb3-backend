<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class DBALWriteRepository extends AbstractDBALRepository implements WriteRepositoryInterface
{
    public function save(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->getTableName()->toNative())
            ->values([
                SchemaConfigurator::UUID_COLUMN => '?',
                SchemaConfigurator::NAME_COLUMN => '?',
                SchemaConfigurator::VISIBLE_COLUMN => '?',
                SchemaConfigurator::PRIVATE_COLUMN => '?',
                SchemaConfigurator::PARENT_UUID_COLUMN => '?',
                SchemaConfigurator::EXCLUDED_COLUMN => '?',
            ])
            ->setParameters([
                $uuid->toString(),
                $name,
                $visibility->sameAs(Visibility::VISIBLE()) ? 1 : 0,
                $privacy->sameAs(Privacy::PRIVACY_PRIVATE()) ? 1 : 0,
                null,
                0,
            ]);

        $queryBuilder->execute();
    }

    public function updateVisible(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::VISIBLE_COLUMN,
            true,
            $uuid
        );
    }

    public function updateInvisible(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::VISIBLE_COLUMN,
            false,
            $uuid
        );
    }

    public function updatePublic(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::PRIVATE_COLUMN,
            false,
            $uuid
        );
    }

    public function updatePrivate(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::PRIVATE_COLUMN,
            true,
            $uuid
        );
    }

    public function updateIncluded(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::EXCLUDED_COLUMN,
            false,
            $uuid
        );
    }

    public function updateExcluded(UUID $uuid): void
    {
        $this->executeUpdate(
            SchemaConfigurator::EXCLUDED_COLUMN,
            true,
            $uuid
        );
    }

    private function executeUpdate(
        string $column,
        bool $value,
        UUID $uuid
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->update($this->getTableName()->toNative())
            ->set($column, '?')
            ->where(SchemaConfigurator::UUID_COLUMN . ' = ?')
            ->setParameters([
                $value ? 1 : 0,
                $uuid->toString(),
            ]);

        $queryBuilder->execute();
    }
}
