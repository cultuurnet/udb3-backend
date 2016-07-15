<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\UiTID\InMemoryCacheDecoratedUsers;
use CultuurNet\UDB3\UiTID\CultureFeedUsers;
use CultuurNet\UDB3\UiTID\UsersInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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

        $app['uitid_users.file_log_handler'] = $app->share(
            function () {
                return new StreamHandler(
                    __DIR__ . '/../../log/uitid_users.log'
                );
            }
        );

        $app['uitid_users.cdbxml_created_by_resolver.logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('cdbxml_created_by_resolver');
                $logger->pushHandler($app['uitid_users.file_log_handler']);

                return $logger;
            }
        );

        $app['uitid_users.cdbxml_created_by_resolver'] = $app->share(
            function (Application $app) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $app['uitid_users']
                );

                $resolver->setLogger(
                    $app['uitid_users.cdbxml_created_by_resolver.logger']
                );

                return $resolver;
            }
        );

        $app['uitid_users.logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('uitid_users');
                $logger->pushHandler(
                    $app['uitid_users.file_log_handler']
                );

                return $logger;
            }
        );

        // Cache in memory when on the command line.
        if ('cli' === php_sapi_name()) {
            $app['uitid_users'] = $app->extend(
                'uitid_users',
                function (UsersInterface $users, Application $app) {
                    $memoryCacheDecoratedUsers = new InMemoryCacheDecoratedUsers(
                        $users
                    );

                    $memoryCacheDecoratedUsers->setLogger(
                        $app['uitid_users.logger']
                    );

                    return $memoryCacheDecoratedUsers;
                }
            );
        }
    }

    public function boot(Application $app)
    {
    }
}
