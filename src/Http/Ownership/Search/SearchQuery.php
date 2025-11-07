<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

final class SearchQuery
{
    private int $start;

    private int $limit;

    private string $sortOrder;

    /** @var SearchParameter[] */
    private array $parameters;

    /**
     * @param SearchParameter[] $parameters
     */
    public function __construct(array $parameters, ?int $start = null, ?int $limit = null, ?string $sortOrder = null)
    {
        $this->parameters = $parameters;

        $this->start = $start !== null ? $start : 0;
        $this->limit = $limit !== null ? $limit : 50;
        $this->sortOrder = $sortOrder !== null ? $sortOrder : 'owner_id';
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSortBy(): string
    {
        if (strpos($this->sortOrder, '-') === 0) {
            return substr($this->sortOrder, 1);
        }
        return $this->sortOrder;
    }

    public function getOrderBy(): string
    {
        if (strpos($this->sortOrder, '-') === 0) {
            return 'DESC';
        }
        return 'ASC';
    }

    /**
     * @return SearchParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
