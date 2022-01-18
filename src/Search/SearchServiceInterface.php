<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface SearchServiceInterface
{
    /**
     * Find UDB3 data based on an arbitrary query.
     *
     * @param string $query
     *   An arbitrary query.
     * @param int $limit
     *   How many items to retrieve.
     * @param int $start
     *   Offset to start from.
     * @param ?array $sort
     *   Sort by fields. Eg. ['created' => 'asc']
     */
    public function search(string $query, int $limit = 30, int $start = 0, ?array $sort = null): Results;
}
