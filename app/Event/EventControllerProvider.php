<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Http\Event\EditEventRestController;
use CultuurNet\UDB3\Http\Event\ReadEventRestController;
use CultuurNet\UDB3\Http\Event\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateSubEventsRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class EventControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'event_editing_controller:createEvent');
        $controllers->delete('/{cdbid}/', 'event_editing_controller:deleteEvent');

        $controllers->get('/{cdbid}/history/', 'event_controller:history');

        $controllers->put('/{cdbid}/audience/', 'event_editing_controller:updateAudience');
        $controllers->put('/{cdbid}/booking-info/', 'event_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contact-point/', 'event_editing_controller:updateContactPoint');
        $controllers->put('/{eventId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->put('/{cdbid}/location/{locationId}/', 'event_editing_controller:updateLocation');
        $controllers->put('/{cdbid}/organizer/{organizerId}/', 'event_editing_controller:updateOrganizer');
        $controllers->delete('/{cdbid}/organizer/{organizerId}/', 'event_editing_controller:deleteOrganizer');
        $controllers->put('/{cdbid}/typical-age-range/', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->delete('/{cdbid}/typical-age-range/', 'event_editing_controller:deleteTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'event_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main/', 'event_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}/', 'event_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}/', 'event_editing_controller:updateImage');

        $controllers->patch('/{eventId}/sub-events/', UpdateSubEventsRequestHandler::class);

        $controllers->post('/{cdbid}/copies/', 'event_editing_controller:copyEvent');

        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually used an incorrect HTTP method.
         */
        $controllers->post('/{itemId}/images/main/', 'event_editing_controller:selectMainImage');
        $controllers->post('/{itemId}/images/{mediaObjectId}/', 'event_editing_controller:updateImage');
        $controllers->post('/{eventId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->post('/{cdbid}/booking-info/', 'event_editing_controller:updateBookingInfo');
        $controllers->post('/{cdbid}/contact-point/', 'event_editing_controller:updateContactPoint');
        $controllers->post('/{cdbid}/typical-age-range/', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->post('/{cdbid}/organizer/', 'event_editing_controller:updateOrganizerFromJsonBody');

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app['event_controller'] = $app->share(
            function (Application $app) {
                return new ReadEventRestController(
                    $app['event_history_repository'],
                    $app['current_user_is_god_user']
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

        $app[UpdateSubEventsRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateSubEventsRequestHandler($app['event_command_bus'])
        );

        $app[UpdateMajorInfoRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateMajorInfoRequestHandler($app['event_command_bus'])
        );
    }

    public function boot(Application $app): void
    {
    }
}
