<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Auth;

use Psr\Http\Message\ServerRequestInterface;

final class PublicRouteWithAuthMatcher
{
    private const ALWAYS = 'always';
    private const PARAM = 'param';

    /** @var PublicRouteRule[] */
    private array $publicRoutes = [];

    private array $publicRoutesWithAuth;

    public function __construct(array $publicRoutesWithAuth)
    {
        $this->publicRoutesWithAuth = $publicRoutesWithAuth;
    }

    public function isAuthenticationRequired(ServerRequestInterface $request): bool
    {
        foreach ($this->publicRoutes as $publicRouteRule) {
            if ($publicRouteRule->matchesRequest($request) && isset($this->publicRoutesWithAuth[$publicRouteRule->getPathPattern()])) {
                $authConfig = $this->publicRoutesWithAuth[$publicRouteRule->getPathPattern()];



                if (empty($authConfig['mode'])) {
                    if(empty($authConfig['param'])) {
                        return false;
                    }

                    // If no mode is set, put a param is configured, assume param mode
                    $authConfig['mode'] = self::PARAM;
                }

                if (mb_strtolower($authConfig['mode']) === self::ALWAYS) {
                    return true;
                }

                if (mb_strtolower($authConfig['mode']) === self::PARAM) {
                    return isset($request->getQueryParams()[$authConfig['param']]) && $request->getQueryParams()[$authConfig['param']] !== false;
                }

                return false;
            }
        }

        return false;
    }

    public function addPublicRoute(PublicRouteRule $publicRouteRule): void
    {
        $this->publicRoutes[] = $publicRouteRule;
    }
}
