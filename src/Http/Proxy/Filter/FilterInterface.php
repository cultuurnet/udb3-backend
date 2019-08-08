<?php

namespace CultuurNet\UDB3\Symfony\Proxy\Filter;

use Psr\Http\Message\RequestInterface;

interface FilterInterface
{
    /**
     * Check if the request matches a certain pattern.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function matches(RequestInterface $request);
}
