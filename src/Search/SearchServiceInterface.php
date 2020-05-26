<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Search;

/**
 * Interface for a service responsible for search-related tasks.
 */
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
     * @param array $sort
     *   Sort by fields. Eg. ['created' => 'asc']
     *
     * @return Results
     */
    public function search(string $query, $limit = 30, $start = 0, array $sort = null);
}
