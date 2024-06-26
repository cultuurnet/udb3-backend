<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Keycloak;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Keycloak\KeycloakUserIdentityResolver;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;

final class KeycloakServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            KeycloakUserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(
            KeycloakUserIdentityResolver::class,
            function () use ($container): KeycloakUserIdentityResolver {
                return new KeycloakUserIdentityResolver(
                    new Client(),
                    $container->get('config')['keycloak']['domain'],
                    $container->get('config')['keycloak']['realm'],
                    $container->get(ManagementTokenProvider::class)->token()
                );
            }
        );
    }
}
