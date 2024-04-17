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
    public function __construct(array $parameters, int $offset = 0, int $limit = 50)
    {
        $this->parameters = $parameters;

        $this->offset = $offset;
        $this->limit = $limit;
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
