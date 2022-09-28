<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\User\GetCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\User\GetUserByEmailRequestHandler;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['cdbxml_created_by_resolver'] = $app->share(
            function (Application $app) {
                $resolver = new CdbXmlCreatedByToUserIdResolver(
                    $app[Auth0UserIdentityResolver::class]
                );

                $resolver->setLogger(LoggerFactory::create($app, LoggerName::forService('xml-conversion', 'created-by-resolver')));

                return $resolver;
            }
        );

        $app[GetUserByEmailRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUserByEmailRequestHandler($app[Auth0UserIdentityResolver::class])
        );

        $app[GetCurrentUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCurrentUserRequestHandler(
                $app[Auth0UserIdentityResolver::class],
                $app[JsonWebToken::class]
            )
        );
    }

    public function boot(Application $app): void
    {
    }
}
