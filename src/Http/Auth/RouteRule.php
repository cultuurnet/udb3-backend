<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use Psr\Http\Message\ServerRequestInterface;

abstract class RouteRule
{
    private string $pathPattern;
    private array $methods;

    public function __construct(string $pathPattern, array $methods)
    {
        $this->pathPattern = $pathPattern;
        $this->methods = $methods;
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        return in_array($method, $this->methods, true) && preg_match($this->pathPattern, $path) === 1;
    }

    public function getPathPattern(): string
    {
        return $this->pathPattern;
    }
}
