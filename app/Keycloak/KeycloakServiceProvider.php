<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Keycloak;

use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\ClientIdResolver;
use CultuurNet\UDB3\User\Keycloak\CachedUserIdentityResolver;
use CultuurNet\UDB3\User\Keycloak\KeycloakClientIdResolver;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;

final class KeycloakServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            CachedUserIdentityResolver::class,
            ClientIdResolver::class,
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
                        $container->get('app_cache'),
                        'user_identity',
                        86400
                    )
                );
            }
        );

        $container->add(
            ClientIdResolver::class,
            function () use ($container): ClientIdResolver {
                return new KeycloakClientIdResolver(
                    new Client(),
                    $container->get('config')['keycloak']['domain'],
                    $container->get('config')['keycloak']['realm'],
                    $container->get(ManagementTokenProvider::class)->token()
                );
            }
        );
    }
}
