<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RequestAuthenticator
{
    private array $publicRoutes = [];

    public function addPublicRoute(string $pathPattern, array $methods = []): void
    {
        $this->publicRoutes[$pathPattern] = $methods;
    }

    public function authenticate(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->isCorsPreflightRequest($request) || $this->isPublicRoute($request)) {
            return null;
        }

        return new ApiProblemJsonResponse(
            ApiProblem::unauthorized('Route is not public')
        );
    }

    private function isCorsPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('access-control-request-method');
    }

    private function isPublicRoute(ServerRequestInterface $request): bool
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        foreach ($this->publicRoutes as $pathPattern => $methods) {
            if (in_array($method, $methods, true) && preg_match($pathPattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
