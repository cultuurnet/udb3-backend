<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Auth0;

use Auth0\SDK\API\Management;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class Auth0ServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[Management::class] = $app::share(
            function (Application $app) {
                // @TODO make domain configurable
                // @TODO the regular JWT is not valid for the management API
                // @see https://auth0.com/docs/api/management/v2/tokens
                return new Management(
                    (string) $app['jwt']->jwtToken(),
                    $app['config']['jwt']['auth0']['domain']
                );
            }
        );

        $app[Auth0UserIdentityResolver::class] = $app::share(
            function (Application $app) {
                return new Auth0UserIdentityResolver(
                    $app[Management::class]
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
