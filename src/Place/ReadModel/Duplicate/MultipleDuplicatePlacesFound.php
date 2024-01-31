<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Duplicate;

use DomainException;

class MultipleDuplicatePlacesFound extends DomainException
{
    public const ERROR_MSG = 'This place already exists. Use the attached query to get existing place(s) for the place you tried to create.';

    private string $query;

    public function __construct(string $query)
    {
        parent::__construct(self::ERROR_MSG, 0, null);
        $this->query = '/places?q=' . $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
