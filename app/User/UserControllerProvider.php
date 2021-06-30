<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Http\User\UserIdentityController;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UserControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app[UserIdentityController::class] = $app->share(
            function (Application $app) {
                return new UserIdentityController(
                    $app[Auth0UserIdentityResolver::class],
                    $app['current_user_id']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('users/emails/{emailAddress}', UserIdentityController::class . ':getByEmailAddress');

        return $controllers;
    }
}
