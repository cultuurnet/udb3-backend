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
                    $app['event_history_repository'],
                    $app['search_v3_serializer']
                );
            }
        );

        $app['event_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditEventRestController(
                    $app['event_editor'],
                    $app['media_manager'],
                    $app['event_iri_generator'],
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['should_auto_approve_new_offer']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', "event_editing_controller:createEvent");
        $controllers->get('/{cdbid}', 'event_controller:get');
        $controllers->delete('/{cdbid}', 'event_editing_controller:deleteEvent');

        $controllers->put('/{cdbid}/audience', 'event_editing_controller:updateAudience');
        $controllers->put('/{cdbid}/bookingInfo', 'event_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contactPoint', 'event_editing_controller:updateContactPoint');
        $controllers->put('/{cdbid}/majorInfo', 'event_editing_controller:updateMajorInfo');
        $controllers->put('/{cdbid}/location/{locationId}', 'event_editing_controller:updateLocation');
        $controllers->put('/{cdbid}/organizer/{organizerId}', 'event_editing_controller:updateOrganizer');
        $controllers->delete('/{cdbid}/organizer/{organizerId}', 'event_editing_controller:deleteOrganizer');
        $controllers->put('/{cdbid}/typicalAgeRange', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->delete('/{cdbid}/typicalAgeRange', 'event_editing_controller:deleteTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'event_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main', 'event_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}', 'event_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}', 'event_editing_controller:updateImage');

        $controllers->get('/{cdbid}/calsum', 'event_controller:getCalendarSummary');

        return $controllers;
    }
}
