<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Place\EditPlaceRestController;
use CultuurNet\UDB3\Http\Place\HistoryPlaceRestController;
use CultuurNet\UDB3\Http\Place\ReadPlaceRestController;
use CultuurNet\UDB3\Http\Place\UpdateCalendarRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class PlaceControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/', 'place_editing_controller:createPlace');
        $controllers->get('/{cdbid}/', 'place_controller:get');
        $controllers->delete('/{cdbid}/', 'place_editing_controller:deletePlace');

        $controllers->get('/{placeId}/history/', 'place_history_controller:get');

        $controllers->put('/{cdbid}/address/{lang}/', 'place_editing_controller:updateAddress');
        $controllers->put('/{cdbid}/booking-info/', 'place_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contact-point/', 'place_editing_controller:updateContactPoint');
        $controllers->put('/{placeId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->put('/{cdbid}/organizer/{organizerId}/', 'place_editing_controller:updateOrganizer');
        $controllers->delete('/{cdbid}/organizer/{organizerId}/', 'place_editing_controller:deleteOrganizer');
        $controllers->delete('/{cdbid}/typical-age-range/', 'place_editing_controller:deleteTypicalAgeRange');
        $controllers->put('/{cdbid}/typical-age-range/', 'place_editing_controller:updateTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'place_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main/', 'place_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}/', 'place_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}/', 'place_editing_controller:updateImage');

        $controllers->get('/{cdbid}/calsum/', 'place_controller:getCalendarSummary');

        $controllers->put('/{placeId}/calendar/', UpdateCalendarRequestHandler::class);

        /**
         * Legacy routes that we need to keep for backward compatibility.
         * These routes usually used an incorrect HTTP method.
         */
        $controllers->get('/{cdbid}/events/', 'place_editing_controller:getEvents');
        $controllers->post('/{itemId}/images/main/', 'place_editing_controller:selectMainImage');
        $controllers->post('/{itemId}/images/{mediaObjectId}/', 'place_editing_controller:updateImage');
        $controllers->post('/{cdbid}/address/{lang}/', 'place_editing_controller:updateAddress');
        $controllers->post('/{cdbid}/typical-age-range/', 'place_editing_controller:updateTypicalAgeRange');
        $controllers->post('/{placeId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->post('/{cdbid}/booking-info/', 'place_editing_controller:updateBookingInfo');
        $controllers->post('/{cdbid}/contact-point/', 'place_editing_controller:updateContactPoint');
        $controllers->post('/{cdbid}/organizer/', 'place_editing_controller:updateOrganizerFromJsonBody');

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app['place_controller'] = $app->share(
            function (Application $app) {
                return new ReadPlaceRestController($app['place_jsonld_repository']);
            }
        );

        $app['place_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditPlaceRestController(
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['media_manager'],
                    $app['place_iri_generator'],
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['should_auto_approve_new_offer']
                );
            }
        );

        $app['place_history_controller'] = $app->share(
            function (Application $app) {
                return new HistoryPlaceRestController(
                    $app['places_history_repository'],
                    $app['current_user_is_god_user']
                );
            }
        );

        $app[UpdateCalendarRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateCalendarRequestHandler($app['event_command_bus'])
        );

        $app[UpdateMajorInfoRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateMajorInfoRequestHandler($app['event_command_bus'])
        );
    }

    public function boot(Application $app): void
    {
    }
}
