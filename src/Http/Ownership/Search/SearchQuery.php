<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership\Search;

final class SearchQuery
{
    private int $offset;
    private int $limit;
    /** @var SearchParameter[] */
    private array $parameters;

    /**
     * @param SearchParameter[] $parameters
     */
    public function __construct(array $parameters, ?int $offset = null, ?int $limit = null)
    {
        $this->parameters = $parameters;

        $this->offset = $offset !== null ? $offset : 0;
        $this->limit = $limit !== null ? $limit : 50;
    }

    public function getOffset(): int
    {
        return $this->offset;
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
