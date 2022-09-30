<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Auth0;

use Auth0\SDK\API\Management;
use Auth0\SDK\Configuration\SdkConfiguration;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\User\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\CacheRepository;
use GuzzleHttp\Client;
use Silex\Application;

final class Auth0ServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return ['auth0.management-token'];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'auth0.management-token',
            function (Application $app): string {
                $provider = new Auth0ManagementTokenProvider(
                    new Auth0ManagementTokenGenerator(
                        new Client(),
                        $app['config']['auth0']['client_id'],
                        $app['config']['auth0']['domain'],
                        $app['config']['auth0']['client_secret']
                    ),
                    new CacheRepository(
                        $app['cache']('auth0-management-token')
                    )
                );
                return $provider->token();
            }
        );

        $container->addShared(
            Auth0UserIdentityResolver::class,
            function (Application $app): Auth0UserIdentityResolver {
                $config = new SdkConfiguration(null, SdkConfiguration::STRATEGY_NONE);
                $config->setDomain($app['config']['auth0']['domain']);
                $config->setManagementToken($app['auth0.management-token']);
                return new Auth0UserIdentityResolver(new Management($config));
            }
        );
    }
}
