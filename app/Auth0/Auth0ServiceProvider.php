<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Auth0;

use Auth0\SDK\API\Management;
use Auth0\SDK\Configuration\SdkConfiguration;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Auth0\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\Auth0\CacheRepository;
use GuzzleHttp\Client;

final class Auth0ServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'auth0.management-token',
            Auth0UserIdentityResolver::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'auth0.management-token',
            function () use ($container): string {
                $provider = new Auth0ManagementTokenProvider(
                    new Auth0ManagementTokenGenerator(
                        new Client(),
                        $container->get('config')['auth0']['client_id'],
                        $container->get('config')['auth0']['domain'],
                        $container->get('config')['auth0']['client_secret']
                    ),
                    new CacheRepository(
                        $container->get('cache')('auth0-management-token')
                    )
                );
                return $provider->token();
            }
        );

        $container->addShared(
            Auth0UserIdentityResolver::class,
            function () use ($container): Auth0UserIdentityResolver {
                $config = new SdkConfiguration(null, SdkConfiguration::STRATEGY_NONE);
                $config->setDomain($container->get('config')['auth0']['domain']);
                $config->setManagementToken($container->get('auth0.management-token'));
                return new Auth0UserIdentityResolver(new Management($config));
            }
        );
    }
}
