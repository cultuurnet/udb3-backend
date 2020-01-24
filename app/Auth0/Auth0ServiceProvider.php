<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Auth0;

use Auth0\SDK\API\Management;
use CultuurNet\UDB3\User\Auth0ManagementTokenGenerator;
use CultuurNet\UDB3\User\Auth0ManagementTokenProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\InMemoryRepository;
use GuzzleHttp\Client;
use Lcobucci\JWT\Parser;
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
                        $app['config']['jwt']['auth0']['client_id'],
                        $app['config']['jwt']['auth0']['domain'],
                        $app['config']['jwt']['auth0']['client_secret']
                    ),
                    new InMemoryRepository(),
                    new Parser()
                );
                return $provider->token();
            }
        );

        $app[Auth0UserIdentityResolver::class] = $app::share(
            function (Application $app) {
                return new Auth0UserIdentityResolver(
                    new Management(
                        $app['auth0.management-token'],
                        $app['config']['jwt']['auth0']['domain']
                    )
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
