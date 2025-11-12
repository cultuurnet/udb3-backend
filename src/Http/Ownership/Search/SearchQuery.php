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

        $this->start = $start ?? 0;
        $this->limit = $limit ?? 50;
        $this->sortOrder = $sortOrder ?? 'owner_id';
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
        if (str_starts_with($this->sortOrder, '-')) {
            return substr($this->sortOrder, 1);
        }
        return $this->sortOrder;
    }

    public function getOrder(): string
    {
        if (str_starts_with($this->sortOrder, '-')) {
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
