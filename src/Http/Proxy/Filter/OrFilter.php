<?php

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;

class OrFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    private $filters;

    /**
     * AndFilter constructor.
     * @param FilterInterface[] $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @inheritdoc
     */
    public function matches(RequestInterface $request)
    {
        foreach ($this->filters as $filter) {
            if ($filter->matches($request)) {
                return true;
            }
        }

        return false;
    }
}
