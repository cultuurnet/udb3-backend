<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories\Search;

use CultuurNet\UDB3\Http\Ownership\Search\SearchQuery;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemCollection;

interface OwnershipSearchRepository
{
    public function save(OwnershipItem $ownershipSearchItem): void;

    public function updateState(string $id, OwnershipState $state): void;

    public function updateRoleId(string $id, ?Uuid $roleId): void;

    public function getById(string $id): OwnershipItem;

    public function search(SearchQuery $searchQuery): OwnershipItemCollection;

    public function searchTotal(SearchQuery $searchQuery): int;
}
