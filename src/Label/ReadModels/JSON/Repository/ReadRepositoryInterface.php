<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface ReadRepositoryInterface
{
    public function getByUuid(UUID $uuid): ?Entity;

    public function getByName(string $name): ?Entity;

    public function canUseLabel(string $userId, string $name): bool;

    public function search(Query $query): array;

    public function searchByLevenshtein(Query $query): array;

    public function searchTotalLabels(Query $query): int;
}
