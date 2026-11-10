<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\Request\QueryParameters;
use Psr\Http\Message\ServerRequestInterface;

abstract class RouteRule
{
    private string $pathPattern;
    private array $methods;
    /** @var string[] */
    private array $excludeQueryParams;

    /**
     * @param string[] $excludeQueryParams
     */
    public function __construct(string $pathPattern, array $methods, array $excludeQueryParams = [])
    {
        $this->pathPattern = $pathPattern;
        $this->methods = $methods;
        $this->excludeQueryParams = $excludeQueryParams;
    }

    public function matchesRequest(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        $isPublicRoute = in_array($method, $this->methods, true) && preg_match($this->pathPattern, $path) === 1;

        if (!$isPublicRoute) {
            return false;
        }

        $queryParameters = new QueryParameters($request);
        foreach ($this->excludeQueryParams as $excludeQueryParam) {
            if ($queryParameters->getAsBoolean($excludeQueryParam, false)) {
                return false;
            }
        }

        return true;
    }
}
