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
        // First check if the decoratee allows use of the label. This way we don't need to check ourselves if the label
        // is maybe public, in which case it is always allowed to be used.
        $canUseLabel = $this->repository->canUseLabel($userId, $name);
        if ($canUseLabel) {
            return true;
        }

        // If the decoratee does not allow the label to be used, check if we allow it in the application config for the
        // given "user" id (i.e. client id)
        $config = $this->clientIdToPermissionsConfig[$userId] ?? null;
        if ($config === null) {
            return false;
        }

        $labels = $config['labels'] ?? [];
        return in_array(strtolower($name), array_map('strtolower', $labels));
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
