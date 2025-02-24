<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Keycloak;

use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Keycloak\CachedUserIdentityResolver;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;
use Predis\Client as RedisClient;

final class KeycloakServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            CachedUserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(
            CachedUserIdentityResolver::class,
            function () use ($container): CachedUserIdentityResolver {
                return new CachedUserIdentityResolver(
                    new KeycloakUserIdentityResolver(
                        new Client(),
                        $container->get('config')['keycloak']['domain'],
                        $container->get('config')['keycloak']['realm'],
                        $container->get(ManagementTokenProvider::class)->token()
                    ),
                    CacheFactory::create(
                        $container->get('temporary_cache'),
                        'user_identity',
                        86400
                    )
                );
            }
        );
    }
}
