<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use DomainException;

class DuplicatePlaceButNoCanonicalPlaceFound extends DomainException
{
    private string $query;

    public function __construct(string $query)
    {
        parent::__construct('This place already exists multiple times in our database. We did not find a canonical place to suggest. Use the attached query to get all possible duplicates.', 0, null);
        $this->query = '/places?q=' . $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
