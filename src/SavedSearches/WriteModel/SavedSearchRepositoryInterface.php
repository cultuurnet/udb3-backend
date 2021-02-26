<?php

namespace CultuurNet\UDB3\SavedSearches\WriteModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;
use ValueObjects\StringLiteral\StringLiteral;

interface SavedSearchRepositoryInterface
{
    public function write(
        StringLiteral $userId,
        StringLiteral $name,
        QueryString $queryString
    ): void;


    public function delete(
        StringLiteral $userId,
        StringLiteral $searchId
    ): void;
}
