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
                // @TODO the regular JWT has not the required scopes. Can we use a server-to-server token instead?
                return new Management(
                    (string) $app['jwt']->jwtToken(),
                    'publiq-acc.eu.auth0.com'
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
