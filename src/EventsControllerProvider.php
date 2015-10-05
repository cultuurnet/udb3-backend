<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Symfony\EventRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class EventsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['event_controller'] = $app->share(
            function (Application $app) {
                return new EventRestController(
                    $app['event_service'],
                    $app['event_editor'],
                    $app['used_labels_memory'],
                    $app['current_user'],
                    null,
                    $app['iri_generator']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/event', "event_controller:createEvent");

        return $controllers;
    }
}
