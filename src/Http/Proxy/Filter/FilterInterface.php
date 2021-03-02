<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use Psr\Http\Message\RequestInterface;

interface FilterInterface
{
    /**
     * Check if the request matches a certain pattern.
     *
     * @return bool
     */
    public function matches(RequestInterface $request);
}
