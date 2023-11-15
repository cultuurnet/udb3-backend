<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use Psr\Http\Message\ServerRequestInterface;

abstract class RouteRule
{
    private string $pathPattern;
    private array $methods;
    private ?string $excludeQueryParam;

    public function __construct(string $pathPattern, array $methods, ?string $excludeGetParams = null)
    {
        $this->pathPattern = $pathPattern;
        $this->methods = $methods;
        $this->excludeQueryParam = $excludeGetParams;
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $isPublicRoute = in_array($method, $this->methods, true) && preg_match($this->pathPattern, $path) === 1;

        if (!$isPublicRoute) {
            return false;
        }

        if ($this->excludeQueryParam === null) {
            return true;
        }

        return ! (new QueryParameters($request))->getAsBoolean($this->excludeQueryParam, false);
    }
}
