<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Command;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

class UpdateSavedSearch extends SavedSearchCommand
{
    protected string $name;

    protected QueryString $query;

    protected string $id;

    public function __construct(
        string $id,
        string $userId,
        string $name,
        QueryString $query
    ) {
        parent::__construct($userId);
        $this->id = $id;

        $this->name = $name;
        $this->query = $query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQuery(): QueryString
    {
        return $this->query;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
