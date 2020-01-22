<?php

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Http\User\UserIdentityController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UserControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['user_identity_controller'] = $app->share(
            function (Application $app) {
                return new UserIdentityController(
                    $app['user_identity_resolver']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('users/emails/{emailAddress}', 'user_identity_controller:getByEmailAddress');

        // @TODO remove after testing!
        $controllers->get('users/{id}', 'user_identity_controller:getById');
        $controllers->get('users/nick/{nick}', 'user_identity_controller:getByNick');

        return $controllers;
    }
}
