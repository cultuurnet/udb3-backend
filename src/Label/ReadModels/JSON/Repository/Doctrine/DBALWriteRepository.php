<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class DBALWriteRepository extends AbstractDBALRepository implements WriteRepositoryInterface
{
    public function save(
        Uuid $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->getTableName())
            ->values([
                ColumnNames::UUID_COLUMN => '?',
                ColumnNames::NAME_COLUMN => '?',
                ColumnNames::VISIBLE_COLUMN => '?',
                ColumnNames::PRIVATE_COLUMN => '?',
                ColumnNames::EXCLUDED_COLUMN => '?',
            ])
            ->setParameters([
                $uuid->toString(),
                $name,
                $visibility->sameAs(Visibility::VISIBLE()) ? 1 : 0,
                $privacy->sameAs(Privacy::private()) ? 1 : 0,
                0,
            ]);

        $queryBuilder->execute();
    }

    public function updateVisible(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::VISIBLE_COLUMN,
            true,
            $uuid
        );
    }

    public function updateInvisible(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::VISIBLE_COLUMN,
            false,
            $uuid
        );
    }

    public function updatePublic(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::PRIVATE_COLUMN,
            false,
            $uuid
        );
    }

    public function updatePrivate(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::PRIVATE_COLUMN,
            true,
            $uuid
        );
    }

    public function updateIncluded(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::EXCLUDED_COLUMN,
            false,
            $uuid
        );
    }

    public function updateExcluded(Uuid $uuid): void
    {
        $this->executeUpdate(
            ColumnNames::EXCLUDED_COLUMN,
            true,
            $uuid
        );
    }

    private function executeUpdate(
        string $column,
        bool $value,
        Uuid $uuid
    ): void {
        $queryBuilder = $this->createQueryBuilder()
            ->update($this->getTableName())
            ->set($column, '?')
            ->where(ColumnNames::UUID_COLUMN . ' = ?')
            ->setParameters([
                $value ? 1 : 0,
                $uuid->toString(),
            ]);

        $queryBuilder->execute();
    }
}
