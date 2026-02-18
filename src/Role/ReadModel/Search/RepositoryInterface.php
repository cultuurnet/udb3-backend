<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use Broadway\Domain\DomainMessage;

interface RepositoryInterface
{
    public function remove(string $uuid): void;

    public function save(string $uuid, string $name, DomainMessage $domainMessage, ?string $constraint = null): void;

    public function search(string $query = '', int $limit = 10, int $start = 0): Results;

    public function updateName(string $uuid, string $name): void;

    public function updateConstraint(string $uuid, ?string $constraint = null): void;
}
