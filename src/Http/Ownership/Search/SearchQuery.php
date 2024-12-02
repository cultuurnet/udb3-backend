<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

final class SearchQuery
{
    private int $start;
    private int $limit;
    /** @var SearchParameter[] */
    private array $parameters;

    /**
     * @param SearchParameter[] $parameters
     */
    public function __construct(array $parameters, ?int $start = null, ?int $limit = null)
    {
        $this->parameters = $parameters;

        $this->start = $start !== null ? $start : 0;
        $this->limit = $limit !== null ? $limit : 50;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return SearchParameter[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
