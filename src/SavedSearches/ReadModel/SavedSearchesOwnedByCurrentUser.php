<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

interface SavedSearchesOwnedByCurrentUser
{
    /**
     * @return SavedSearch[]
     */
    public function ownedByCurrentUser(): array;
}
