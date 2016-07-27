<?php

namespace CultuurNet\UDB3\Silex\User;

use CultuurNet\UDB3\Symfony\User\SearchUserController;
use CultuurNet\UDB3\Symfony\User\UserLabelMemoryRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UserControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['user_label_memory_controller'] = $app->share(
            function (Application $app) {
                return new UserLabelMemoryRestController(
                    $app['used_labels_memory'],
                    $app['current_user']
                );
            }
        );

        $app['user_search_controller'] = $app->share(
            function (Application $app) {
                return new SearchUserController(
                    $app['culturefeed']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('api/1.0/user/labels', 'user_label_memory_controller:all');

        $controllers->get('users/', 'user_search_controller:search');

        return $controllers;
    }
}
