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
     * @param SavedSearchRepositoryInterface $repository,...
     *   Optionally an unlimited list of repositories to combine.
     *
     * @throws \InvalidArgumentException
     *   When one of the provided arguments does not implement
     *   SavedSearchRepositoryInterface.
     */
    public function __construct()
    {
        $arguments = func_get_args();
        foreach ($arguments as $argument) {
            if (!($argument instanceof SavedSearchRepositoryInterface)) {
                $error = 'Argument provided should implement SavedSearchRepositoryInterface. ('
                    . get_class($argument) . ' given.)';
                throw new \InvalidArgumentException($error);
            }

            $this->addRepository($argument);
        }
    }

    /**
     * @param SavedSearchRepositoryInterface $repository
     */
    public function addRepository(SavedSearchRepositoryInterface $repository): void
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
