<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class AppConfigReadRepositoryDecorator implements ReadRepositoryInterface
{
    private ReadRepositoryInterface $repository;
    /**
     * @var array<string, array>
     */
    private array $clientIdToPermissionsConfig;

    public function __construct(ReadRepositoryInterface $repository, array $clientIdToPermissionsConfig)
    {
        $this->repository = $repository;
        $this->clientIdToPermissionsConfig = $clientIdToPermissionsConfig;
    }


    public function getByUuid(UUID $uuid): ?Entity
    {
        return $this->repository->getByUuid($uuid);
    }

    public function getByName(string $name): ?Entity
    {
        return $this->repository->getByName($name);
    }

    public function canUseLabel(string $userId, string $name): bool
    {
        $config = $this->clientIdToPermissionsConfig[$userId] ?? null;

        if ($config === null) {
            return $this->repository->canUseLabel($userId, $name);
        }

        $labels = $config['labels'] ?? [];

        return in_array($name, $labels);
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