<?php

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\CultureFeedUserIdentityDetailsFactory;
use CultuurNet\UDB3\User\CultureFeedUserIdentityResolver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['user_identity_resolver'] = $app->share(
            function (Application $app) {
                $toggles = $app['toggles'];
                if ($toggles->active('auth0-user-management', $app['toggles.context'])) {
                    return $app[Auth0UserIdentityResolver::class];
                }

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

        $app['cdbxml_created_by_resolver.file_log_handler'] = $app->share(
            function () {
                return new StreamHandler(
                    __DIR__ . '/../../log/cdbxml_created_by_resolver.log'
                );
            }
        );

        $app['cdbxml_created_by_resolver.logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('cdbxml_created_by_resolver');
                $logger->pushHandler($app['cdbxml_created_by_resolver.file_log_handler']);

                return $logger;
            }
        );

        $app['cdbxml_created_by_resolver'] = $app->share(
            function (Application $app) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $app['user_identity_resolver']
                );

                $resolver->setLogger(
                    $app['cdbxml_created_by_resolver.logger']
                );

                return $resolver;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
