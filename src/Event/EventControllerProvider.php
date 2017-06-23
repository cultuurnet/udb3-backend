<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Symfony\Event\EditEventRestController;
use CultuurNet\UDB3\Symfony\Event\ReadEventRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class EventControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        $app['event_controller'] = $app->share(
            function (Application $app) {
                return new ReadEventRestController(
                    $app['event_service'],
                    $app['event_history_repository']
                );
            }
        );

        $app['event_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditEventRestController(
                    $app['event_editor'],
                    $app['media_manager'],
                    $app['event_iri_generator']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', "event_editing_controller:createEvent");
        $controllers->get('/{cdbid}', 'event_controller:get');
        $controllers->put('/{cdbid}/audience', 'event_editing_controller:updateAudience');
        $controllers->put('/{cdbid}/bookingInfo', 'event_editing_controller:updateBookingInfo');

        return $controllers;
    }
}
