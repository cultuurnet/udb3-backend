<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class RequestAuthenticator
{
    private const BEARER = 'Bearer ';

    private array $publicRoutes = [];
    private JwtAuthenticationProvider $jwtAuthenticator;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        JwtAuthenticationProvider $jwtAuthenticator,
        TokenStorageInterface $tokenStorage
    ) {
        $this->jwtAuthenticator = $jwtAuthenticator;
        $this->tokenStorage = $tokenStorage;
    }

    public function addPublicRoute(string $pathPattern, array $methods = []): void
    {
        $this->publicRoutes[$pathPattern] = $methods;
    }

    public function authenticate(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->isCorsPreflightRequest($request) || $this->isPublicRoute($request)) {
            return null;
        }

        $authorizationHeader = $request->getHeader('authorization');
        if (empty($authorizationHeader)) {
            return new ApiProblemJsonResponse(ApiProblem::unauthorized('Authorization header missing.'));
        }

        $authorizationHeader = $authorizationHeader[0];
        $startsWithBearer = strpos($authorizationHeader, self::BEARER) === 0;
        if (!$startsWithBearer) {
            return new ApiProblemJsonResponse(
                ApiProblem::unauthorized(
                    'Authorization header must start with "' . self::BEARER . '", followed by your token.'
                )
            );
        }

        $tokenString = substr($authorizationHeader, strlen(self::BEARER));
        try {
            $token = new JsonWebToken($tokenString, false);
        } catch (InvalidArgumentException $e) {
            return new ApiProblemJsonResponse(
                ApiProblem::unauthorized('Token "' . $tokenString . '" is not a valid JWT.')
            );
        }

        try {
            $token = $this->jwtAuthenticator->authenticate($token);
        } catch (AuthenticationException $authenticationException) {
            return new ApiProblemJsonResponse(ApiProblem::unauthorized($authenticationException->getMessage()));
        }

        $this->tokenStorage->setToken($token);
        return null;
    }

    private function isCorsPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('access-control-request-method');
    }

    public function isPublicRoute(ServerRequestInterface $request): bool
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
