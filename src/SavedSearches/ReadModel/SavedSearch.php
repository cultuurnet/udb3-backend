<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\ReadModel;

use CultuurNet\UDB3\SavedSearches\Properties\QueryString;

class SavedSearch implements \JsonSerializable
{
    protected ?string $id;

    protected string $name;

    /**
     * @var QueryString
     */
    protected $query;

    public function __construct(string $name, QueryString $query, string $id = null)
    {
        $this->name = $name;
        $this->query = $query;
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $serializedSavedSearch = [
            'name' => $this->name,
            'query' => $this->query->toString(),
        ];

        if ($this->id) {
            $serializedSavedSearch['id'] = (string) $this->id;
        }

        return $serializedSavedSearch;
    }
}
