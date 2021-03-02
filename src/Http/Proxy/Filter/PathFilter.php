<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Proxy\Filter;

use CultuurNet\UDB3\Http\Proxy\FilterPathRegex;
use Psr\Http\Message\RequestInterface;

class PathFilter implements FilterInterface
{
    /**
     * @var FilterPathRegex
     */
    private $path;

    public function __construct(FilterPathRegex $path)
    {
        $this->path = $path;
    }

    /**
     * @inheritdoc
     */
    public function matches(RequestInterface $request)
    {
        $requestedPath = new FilterPathRegex($request->getUri()->getPath());
        $pathPattern = '/' . $this->path->toNative() . '/';
        return !!preg_match($pathPattern, $requestedPath->toNative());
    }
}
