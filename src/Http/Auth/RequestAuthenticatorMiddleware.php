<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticationException;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepository as ApiKeyConsumerReadRepository;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecification as ApiKeyConsumerSpecification;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Auth\Jwt\JwtValidator;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\User\CurrentUser;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestAuthenticatorMiddleware implements MiddlewareInterface
{
    private const BEARER = 'Bearer ';

    /** @var PublicRouteRule[] */
    private array $publicRoutes = [];

    /** @var PermissionRestrictedRouteRule[] */
    private array $permissionRestrictedRoutes = [];

    private ?JsonWebToken $token = null;
    private ?ApiKey $apiKey = null;

    private JwtValidator $uitIdV1JwtValidator;
    private JwtValidator $uitIdV2JwtValidator;
    private ApiKeyAuthenticator $apiKeyAuthenticator;
    private ApiKeyConsumerReadRepository $apiKeyConsumerReadRepository;
    private ApiKeyConsumerSpecification $apiKeyConsumerPermissionCheck;
    private UserPermissionsReadRepositoryInterface $userPermissionReadRepository;

    public function __construct(
        JwtValidator $uitIdV1JwtValidator,
        JwtValidator $uitIdV2JwtValidator,
        ApiKeyAuthenticator $apiKeyAuthenticator,
        ApiKeyConsumerReadRepository $apiKeyConsumerReadRepository,
        ApiKeyConsumerSpecification $apiKeyConsumerPermissionCheck,
        UserPermissionsReadRepositoryInterface $userPermissionsReadRepository
    ) {
        $this->uitIdV1JwtValidator = $uitIdV1JwtValidator;
        $this->uitIdV2JwtValidator = $uitIdV2JwtValidator;
        $this->apiKeyAuthenticator = $apiKeyAuthenticator;
        $this->apiKeyConsumerReadRepository = $apiKeyConsumerReadRepository;
        $this->apiKeyConsumerPermissionCheck = $apiKeyConsumerPermissionCheck;
        $this->userPermissionReadRepository = $userPermissionsReadRepository;
    }

    public function addPublicRoute(string $pathPattern, array $methods = [], ?string $excludeQueryParam = null): void
    {
        $this->publicRoutes[] = new PublicRouteRule($pathPattern, $methods, $excludeQueryParam);
    }

    public function addPermissionRestrictedRoute(string $pathPattern, array $methods, Permission $permission): void
    {
        $this->permissionRestrictedRoutes[] = new PermissionRestrictedRouteRule($pathPattern, $methods, $permission);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->authenticate($request);
        return $handler->handle($request);
    }

    /**
     * @throws ApiProblem
     */
    private function authenticate(ServerRequestInterface $request): void
    {
        if ($this->isCorsPreflightRequest($request) || $this->isPublicRoute($request)) {
            return;
        }

        $this->authenticateToken($request);

        // Requests that use a token from the JWT provider (v1 or v2) require an API key from UiTID v1.
        // Requests that use a token that they got directly from Auth0 do not require an API key.
        // The difference can be checked by checking if the token has a client id, which is always missing in tokens
        // from the JWT providers.
        if ($this->token->getClientId() === null || $this->token->getType() === JsonWebToken::UIT_ID_V2_JWT_PROVIDER_TOKEN) {
            $this->authenticateApiKey($request);
        }

        $this->checkPermission($request);
    }

    public function getToken(): ?JsonWebToken
    {
        return $this->token;
    }

    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    public function getCurrentUser(): CurrentUser
    {
        $userId = $this->token ? $this->token->getUserId() : null;
        return new CurrentUser($userId);
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
            $this->token = new JsonWebToken($tokenString);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::unauthorized('Token "' . $tokenString . '" is not a valid JWT.');
        }

        $isV1 = $this->token->getType() === JsonWebToken::UIT_ID_V1_JWT_PROVIDER_TOKEN;
        $validator = $isV1 ? $this->uitIdV1JwtValidator : $this->uitIdV2JwtValidator;

        $validator->verifySignature($this->token);
        $validator->validateClaims($this->token);
    }

    private function authenticateApiKey(ServerRequestInterface $request): void
    {
        $apiKeyReader = new CompositeApiKeyReader(
            new QueryParameterApiKeyReader('apiKey'),
            new CustomHeaderApiKeyReader('X-Api-Key')
        );
        $this->apiKey = $apiKeyReader->read($request);

        if ($this->apiKey === null) {
            throw ApiProblem::unauthorized(
                'The given token requires an API key, but no x-api-key header or apiKey URL parameter found.'
            );
        }

        try {
            $this->apiKeyAuthenticator->authenticate($this->apiKey);
        } catch (ApiKeyAuthenticationException $e) {
            throw ApiProblem::unauthorized($e->getMessage());
        }

        $consumer = $this->apiKeyConsumerReadRepository->getConsumer($this->apiKey);
        if ($consumer === null) {
            throw ApiProblem::unauthorized('No consumer details could be found for the given API key.');
        }

        $canAccessEntryApi = $this->apiKeyConsumerPermissionCheck->satisfiedBy($consumer);
        if (!$canAccessEntryApi) {
            throw ApiProblem::forbidden('Given API key is not authorized to use Entry API.');
        }
    }

    private function checkPermission(ServerRequestInterface $request): void
    {
        foreach ($this->permissionRestrictedRoutes as $permissionRestrictedRoute) {
            if (!$permissionRestrictedRoute->matchesRequest($request)) {
                continue;
            }

            $user = $this->getCurrentUser();
            $permission = $permissionRestrictedRoute->getPermission();
            if (!$user->isGodUser() &&
                !$this->userPermissionReadRepository->hasPermission($user->getId(), $permission)) {
                throw ApiProblem::forbidden('This request requires the "' . $permission->toString() . '" permission');
            }
        }
    }

    private function isCorsPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('access-control-request-method');
    }

    private function isPublicRoute(ServerRequestInterface $request): bool
    {
        foreach ($this->publicRoutes as $publicRouteRule) {
            if ($publicRouteRule->matchesRequest($request)) {
                return true;
            }
        }
        return false;
    }
}
