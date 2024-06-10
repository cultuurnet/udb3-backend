<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Keycloack;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Keycloack\KeycloackUserIdentityResolver;
use CultuurNet\UDB3\User\Keycloack\KeycloakManagementTokenGenerator;
use GuzzleHttp\Client;

final class KeycloackServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            KeycloakManagementTokenGenerator::class,
            KeycloackUserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(
            KeycloakManagementTokenGenerator::class,
            function () use ($container): KeycloakManagementTokenGenerator {
                return new KeycloakManagementTokenGenerator(
                    new Client(),
                    $container->get('config')['keycloack']['domain'],
                    $container->get('config')['keycloack']['client_id'],
                    $container->get('config')['keycloack']['client_secret']
                );
            }
        );

        $container->add(
            KeycloackUserIdentityResolver::class,
            function () use ($container): KeycloackUserIdentityResolver {
                return new KeycloackUserIdentityResolver(
                    new Client(),
                    $container->get('config')['keycloack']['domain'],
                    $container->get('config')['keycloack']['realm'],
                    $container->get(KeycloakManagementTokenGenerator::class)
                );
            }
        );
    }
}
