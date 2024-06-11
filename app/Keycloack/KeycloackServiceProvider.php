<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Keycloack;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Keycloack\KeycloackUserIdentityResolver;
use CultuurNet\UDB3\User\ManagementToken\ManagementTokenProvider;
use GuzzleHttp\Client;

final class KeycloackServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            KeycloackUserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(
            KeycloackUserIdentityResolver::class,
            function () use ($container): KeycloackUserIdentityResolver {
                return new KeycloackUserIdentityResolver(
                    new Client(),
                    $container->get('config')['keycloack']['domain'],
                    $container->get('config')['keycloack']['realm'],
                    $container->get(ManagementTokenProvider::class)->token()
                );
            }
        );
    }
}
