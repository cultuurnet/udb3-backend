<?php

namespace CultuurNet\UDB3\Search;

/**
 * Interface for search services that are only interested in the result count,
 * not in the actual results.
 */
interface CountingSearchServiceInterface
{
    /**
     * Count results based on an arbitrary query.
     *
     * @param string $query
     *   An arbitrary query
     *
     * @return int
     */
    public function search(string $query): int;
}
