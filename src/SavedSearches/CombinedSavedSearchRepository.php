<?php

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;

class CombinedSavedSearchRepository implements SavedSearchRepositoryInterface
{
    /**
     * @var SavedSearchRepositoryInterface[]
     */
    protected $repositories;

    /**
     * @param SavedSearchRepositoryInterface ...$repositories
     *   Optionally an unlimited list of repositories to combine.
     */
    public function __construct(SavedSearchRepositoryInterface ...$repositories)
    {
        foreach ($repositories as $repository) {
            $this->addRepository($repository);
        }
    }

    private function addRepository(SavedSearchRepositoryInterface $repository): void
    {
        $this->repositories[] = $repository;
    }

    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array
    {
        $savedSearches = [];

        foreach ($this->repositories as $repository) {
            $append = array_values($repository->ownedByCurrentUser());
            $savedSearches = array_merge($savedSearches, $append);
        }

        return $savedSearches;
    }
}
