<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

class SavedSearch implements \JsonSerializable
{
    protected ?string $id;
    protected ?string $userId;

    protected string $name;

    protected QueryString $query;

    public function __construct(string $name, QueryString $query, string $id = null, string $userId = null)
    {
        $this->name = $name;
        $this->query = $query;
        $this->id = $id;
        $this->userId = $userId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function jsonSerialize(): array
    {
        $serializedSavedSearch = [
            'name' => $this->name,
            'query' => $this->query->clean()->toString(),
        ];

        if ($this->id) {
            $serializedSavedSearch['id'] = (string) $this->id;
        }

        return $serializedSavedSearch;
    }
}
