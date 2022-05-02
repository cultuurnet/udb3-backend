<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Services;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Query;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\StringLiteral;

/**
 * @deprecated
 *   Use an ReadRepositoryInterface implementation directly.
 */
final class ReadService implements ReadServiceInterface
{
    private ReadRepositoryInterface $readRepository;

    public function __construct(ReadRepositoryInterface $readRepository)
    {
        $this->readRepository = $readRepository;
    }

    public function getByUuid(UUID $uuid): ?Entity
    {
        return $this->readRepository->getByUuid($uuid);
    }

    public function getByName(StringLiteral $identifier): ?Entity
    {
        return $this->readRepository->getByName($identifier);
    }

    public function search(Query $query): ?array
    {
        return $this->readRepository->search($query);
    }

    public function searchTotalLabels(Query $query): int
    {
        return $this->readRepository->searchTotalLabels($query);
    }
}
