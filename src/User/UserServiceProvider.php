<?php

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Cdb\ItemBaseAdapterFactory;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\UiTID\InMemoryCacheDecoratedUsers;
use CultuurNet\UDB3\UiTID\CultureFeedUsers;
use CultuurNet\UDB3\UiTID\UsersInterface;
use CultuurNet\UDB3\User\CultureFeedUserIdentityDetailsFactory;
use CultuurNet\UDB3\User\CultureFeedUserIdentityResolver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    const ITEM_BASE_ADAPTER_FACTORY = 'uitid.item_base_adapter_factory';

    public function register(Application $app)
    {
        $app['user_identity_resolver'] = $app->share(
            function (Application $app) {
                return new CultureFeedUserIdentityResolver(
                    $app['culturefeed'],
                    $app['culturefeed_user_identity_factory']
                );
            }
        );

        $app['culturefeed_user_identity_factory'] = $app->share(
            function (Application $app) {
                return new CultureFeedUserIdentityDetailsFactory();
            }
        );

        /**
         * @deprecated
         *   Use $app['user_identity_resolver'] instead.
         */
        $app['uitid_users'] = $app->share(
            function (Application $app) {
                return new CultureFeedUsers(
                    $app['user_identity_resolver']
                );
            }
        );

        /**
         * This service can be used to wrap legacy UDB2 cdbxml actor/event objects
         * with methods convenient for UDB3.
         */
        $app[self::ITEM_BASE_ADAPTER_FACTORY] = $app->share(
            function (Application $app) {
                return new ItemBaseAdapterFactory(
                    $app['uitid_users.cdbxml_created_by_resolver']
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
