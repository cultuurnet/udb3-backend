<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\WriteModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

interface SavedSearchRepositoryInterface
{
    public function insert(
        string $id,
        string $userId,
        string $name,
        QueryString $queryString
    ): void;

    public function update(
        string $id,
        string $userId,
        string $name,
        QueryString $queryString
    ): void;

    public function delete(
        string $userId,
        string $searchId
    ): void;
}
