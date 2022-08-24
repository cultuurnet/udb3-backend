<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationProvider;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class RequestAuthenticator
{
    private const BEARER = 'Bearer ';

    private array $publicRoutes = [];
    private ?JsonWebToken $token = null;
    private JwtAuthenticationProvider $jwtAuthenticator;

    public function __construct(JwtAuthenticationProvider $jwtAuthenticator)
    {
        $this->jwtAuthenticator = $jwtAuthenticator;
    }

    public function addPublicRoute(string $pathPattern, array $methods = []): void
    {
        $this->publicRoutes[$pathPattern] = $methods;
    }

    /**
     * @throws ApiProblem
     */
    public function authenticate(ServerRequestInterface $request): void
    {
        if ($this->isCorsPreflightRequest($request) || $this->isPublicRoute($request)) {
            return;
        }

        $this->authenticateToken($request);
    }

    public function getToken(): ?JsonWebToken
    {
        return $this->token;
    }

    private function authenticateToken(ServerRequestInterface $request): void
    {
        $authorizationHeader = $request->getHeader('authorization');
        if (empty($authorizationHeader)) {
            throw ApiProblem::unauthorized('Authorization header missing.');
        }

        $authorizationHeader = $authorizationHeader[0];
        $startsWithBearer = strpos($authorizationHeader, self::BEARER) === 0;
        if (!$startsWithBearer) {
            throw ApiProblem::unauthorized(
                'Authorization header must start with "' . self::BEARER . '", followed by your token.'
            );
        }

        $tokenString = substr($authorizationHeader, strlen(self::BEARER));
        try {
            $this->token = new JsonWebToken($tokenString, false);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::unauthorized('Token "' . $tokenString . '" is not a valid JWT.');
        }

        try {
            $this->token = $this->jwtAuthenticator->authenticate($this->token);
        } catch (AuthenticationException $authenticationException) {
            throw ApiProblem::unauthorized($authenticationException->getMessage());
        }
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
