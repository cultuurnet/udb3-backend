<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches;

use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchesOwnedByCurrentUser;

class CombinedSavedSearchRepository implements SavedSearchesOwnedByCurrentUser
{
    /**
     * @var SavedSearchesOwnedByCurrentUser[]
     */
    protected array $repositories;

    public function __construct(SavedSearchesOwnedByCurrentUser ...$repositories)
    {
        $this->repositories = $repositories;
    }

    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array
    {
        $savedSearches = [];

        foreach ($this->repositories as $repository) {
            $append = array_values($repository->ownedByCurrentUser());
            foreach ($append as $item) {
                $savedSearches[] = $item;
            }
        }

        return $savedSearches;
    }
}
