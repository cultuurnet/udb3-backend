<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Auth0;

use Auth0\SDK\API\Management;
use Auth0\SDK\Configuration\SdkConfiguration;
use CultuurNet\UDB3\User\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\CacheRepository;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class Auth0ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['auth0.management-token'] = $app::share(
            function (Application $app) {
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

        $app[Auth0UserIdentityResolver::class] = $app::share(
            function (Application $app) {
                $config = new SdkConfiguration(null, SdkConfiguration::STRATEGY_NONE);
                $config->setDomain($app['config']['auth0']['domain']);
                $config->setManagementToken($app['auth0.management-token']);
                return new Auth0UserIdentityResolver(new Management($config));
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
