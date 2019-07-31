<?php

namespace CultuurNet\UDB3\Silex\Moderation;

use CultuurNet\UDB3\Symfony\Search\SearchController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ModerationControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['moderation_search_controller'] = $app->share(
            function (Application $app) {
                return new SearchController(
                    $app['moderation_search_service']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('moderation', 'moderation_search_controller:search');

        return $controllers;
    }
}
