<?php

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Symfony\User\SearchQueryFactory;
use CultuurNet\UDB3\Symfony\User\SearchUserController;
use CultuurNet\UDB3\Symfony\User\UserIdentityController;
use CultuurNet\UDB3\Symfony\User\UserLabelMemoryRestController;
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

        $app['user_search_controller'] = $app->share(
            function (Application $app) {
                return new SearchUserController(
                    $app['culturefeed'],
                    new SearchQueryFactory(),
                    $app['culturefeed_user_identity_factory']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('users/{userId}', 'user_identity_controller:getByUserId');
        $controllers->get('users/emails/{emailAddress}', 'user_identity_controller:getByEmailAddress');

        $controllers->get('users/', 'user_search_controller:search');

        return $controllers;
    }
}
