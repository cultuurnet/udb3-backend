<?php

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;

class AndFilter implements FilterInterface
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
        $matches = false;

        foreach ($this->filters as $filter) {
            $matches = $filter->matches($request);

            if (!$matches) {
                break;
            }
        }

        return $matches;
    }
}
