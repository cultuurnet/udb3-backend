<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class GodUserReadRepositoryDecorator implements ReadRepositoryInterface
{
    /**
     * @var ReadRepositoryInterface
     */
    private $repository;

    /**
     * @var string[]
     */
    private $godUserIds;

    /**
     * @param ReadRepositoryInterface $readRepository
     * @param array $godUserIds
     */
    public function __construct(ReadRepositoryInterface $readRepository, array $godUserIds)
    {
        $this->repository = $readRepository;
        $this->godUserIds = $godUserIds;
    }

    /**
     * @inheritdoc
     */
    public function getByUuid(UUID $uuid)
    {
        return $this->repository->getByUuid($uuid);
    }

    /**
     * @inheritdoc
     */
    public function getByName(StringLiteral $name)
    {
        return $this->repository->getByName($name);
    }

    /**
     * @inheritdoc
     */
    public function canUseLabel(StringLiteral $userId, StringLiteral $name)
    {
        if (in_array($userId->toNative(), $this->godUserIds)) {
            // God users can use any label.
            return true;
        }

        return $this->repository->canUseLabel($userId, $name);
    }

    /**
     * @inheritdoc
     */
    public function search(Query $query)
    {
        return $this->repository->search($query);
    }

    /**
     * @inheritdoc
     */
    public function searchTotalLabels(Query $query)
    {
        return $this->repository->searchTotalLabels($query);
    }
}
