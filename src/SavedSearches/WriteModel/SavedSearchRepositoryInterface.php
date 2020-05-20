<?php

namespace CultuurNet\UDB3\SavedSearches\WriteModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use ValueObjects\StringLiteral\StringLiteral;

interface SavedSearchRepositoryInterface
{
    /**
     * @param StringLiteral $userId
     * @param StringLiteral $name
     * @param QueryString $queryString
     * @return void
     */
    public function write(
        StringLiteral $userId,
        StringLiteral $name,
        QueryString $queryString
    ): void;

    /**
     * @param StringLiteral $userId
     * @param StringLiteral $searchId
     */
    public function delete(
        StringLiteral $userId,
        StringLiteral $searchId
    ): void;
}
