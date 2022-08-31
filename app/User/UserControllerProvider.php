<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Http\User\GetCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\User\GetUserByEmailRequestHandler;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UserControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[GetUserByEmailRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUserByEmailRequestHandler($app[Auth0UserIdentityResolver::class])
        );

        $app[GetCurrentUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCurrentUserRequestHandler(
                $app[Auth0UserIdentityResolver::class],
                $app[JsonWebToken::class]
            )
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('users/emails/{email}/', GetUserByEmailRequestHandler::class);
        $controllers->get('user/', GetCurrentUserRequestHandler::class);

        return $controllers;
    }
}
