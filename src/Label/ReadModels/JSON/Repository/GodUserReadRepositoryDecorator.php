<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

final class GodUserReadRepositoryDecorator implements ReadRepositoryInterface
{
    private ReadRepositoryInterface $repository;

    /**
     * @var string[]
     */
    private array $godUserIds;

    public function __construct(ReadRepositoryInterface $readRepository, array $godUserIds)
    {
        $this->repository = $readRepository;
        $this->godUserIds = $godUserIds;
    }

    public function getByUuid(Uuid $uuid): ?Entity
    {
        return $this->repository->getByUuid($uuid);
    }

    public function getByName(string $name): ?Entity
    {
        return $this->repository->getByName($name);
    }

    public function canUseLabel(string $userId, string $name): bool
    {
        if (in_array($userId, $this->godUserIds)) {
            // God users can use any label.
            return true;
        }

        return $this->repository->canUseLabel($userId, $name);
    }

    public function search(Query $query): array
    {
        return $this->repository->search($query);
    }

    public function searchTotalLabels(Query $query): int
    {
        return $this->repository->searchTotalLabels($query);
    }
}
