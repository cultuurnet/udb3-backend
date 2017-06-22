<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Symfony\Event\EditEventRestController;
use CultuurNet\UDB3\Symfony\Event\ReadEventRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class DeprecatedEventControllerProvider implements ControllerProviderInterface
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

        $controllers
            ->get('event/{cdbid}', 'event_controller:get')
            ->bind('event');
        $controllers->delete('event/{cdbid}', 'event_editing_controller:deleteEvent');

        $controllers
            ->get('event/{cdbid}/history', 'event_controller:history')
            ->bind('event-history');

        $controllers->post('event', "event_editing_controller:createEvent");

        $controllers->post('event/{itemId}/images', 'event_editing_controller:addImage');
        $controllers->post('event/{itemId}/images/main', 'event_editing_controller:selectMainImage');
        $controllers->post('event/{itemId}/images/{mediaObjectId}', 'event_editing_controller:updateImage');
        $controllers->delete('event/{itemId}/images/{mediaObjectId}', 'event_editing_controller:removeImage');

        $controllers->post('event/{cdbid}/nl/description', 'event_editing_controller:updateDescription');
        $controllers->post('event/{cdbid}/typical-age-range', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->delete('event/{cdbid}/typical-age-range', 'event_editing_controller:deleteTypicalAgeRange');
        $controllers->post('event/{cdbid}/major-info', 'event_editing_controller:updateMajorInfo');
        $controllers->post('event/{cdbid}/bookingInfo', 'event_editing_controller:updateBookingInfo');
        $controllers->post('event/{cdbid}/contactPoint', 'event_editing_controller:updateContactPoint');
        $controllers->post('event/{cdbid}/organizer', 'event_editing_controller:updateOrganizer');
        $controllers->delete('event/{cdbid}/organizer/{organizerId}', 'event_editing_controller:deleteOrganizer');
        $controllers->put('event/{cdbid}/audience', 'event_editing_controller:updateAudience');
        $controllers->post('event/{cdbid}/copies/', 'event_editing_controller:copyEvent');

        return $controllers;
    }
}
