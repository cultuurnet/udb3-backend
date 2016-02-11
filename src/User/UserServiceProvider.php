<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\UiTID\InMemoryCacheDecoratedUsers;
use CultuurNet\UDB3\UiTID\CultureFeedUsers;
use CultuurNet\UDB3\UiTID\UsersInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['uitid_users'] = $app->share(
            function (Application $app) {
                return new CultureFeedUsers(
                    $app['culturefeed']
                );
            }
        );

        // Cache in memory when on the command line.
        if ('cli' === php_sapi_name()) {
            $app['uitid_users'] = $app->extend(
                'uitid_users',
                function (UsersInterface $users) {
                    return new InMemoryCacheDecoratedUsers($users);
                }
            );
        }
    }

    public function boot(Application $app)
    {

    }
}
