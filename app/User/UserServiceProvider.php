<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['user_identity_resolver'] = $app->share(
            function (Application $app) {
                return $app[Auth0UserIdentityResolver::class];
            }
        );

        $app['cdbxml_created_by_resolver'] = $app->share(
            function (Application $app) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $app['user_identity_resolver']
                );

                $resolver->setLogger(LoggerFactory::create($app, new LoggerName('cdbxml_created_by_resolver')));

                return $resolver;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
