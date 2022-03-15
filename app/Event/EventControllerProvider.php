<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Http\Event\CopyEventRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteThemeRequestHandler;
use CultuurNet\UDB3\Http\Event\EditEventRestController;
use CultuurNet\UDB3\Http\Event\ImportEventRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateAudienceRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateLocationRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateSubEventsRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateThemeRequestHandler;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use Ramsey\Uuid\UuidFactory;
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

        $controllers->post('/', ImportEventRequestHandler::class);

        $controllers->put('/{eventId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->put('/{eventId}/location/{locationId}/', UpdateLocationRequestHandler::class);
        $controllers->patch('/{eventId}/sub-events/', UpdateSubEventsRequestHandler::class);
        $controllers->put('/{eventId}/theme/{termId}/', UpdateThemeRequestHandler::class);
        $controllers->delete('/{eventId}/theme/', DeleteThemeRequestHandler::class);
        $controllers->put('/{eventId}/audience/', UpdateAudienceRequestHandler::class);
        $controllers->post('/{eventId}/copies/', CopyEventRequestHandler::class);

        $controllers->put('/{cdbid}/booking-info/', 'event_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contact-point/', 'event_editing_controller:updateContactPoint');
        $controllers->put('/{cdbid}/organizer/{organizerId}/', 'event_editing_controller:updateOrganizer');
        $controllers->delete('/{cdbid}/organizer/{organizerId}/', 'event_editing_controller:deleteOrganizer');
        $controllers->put('/{cdbid}/typical-age-range/', 'event_editing_controller:updateTypicalAgeRange');
        $controllers->delete('/{cdbid}/typical-age-range/', 'event_editing_controller:deleteTypicalAgeRange');

        $controllers->post('/{itemId}/images/', 'event_editing_controller:addImage');
        $controllers->put('/{itemId}/images/main/', 'event_editing_controller:selectMainImage');
        $controllers->delete('/{itemId}/images/{mediaObjectId}/', 'event_editing_controller:removeImage');
        $controllers->put('/{itemId}/images/{mediaObjectId}/', 'event_editing_controller:updateImage');

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

        $app[ImportEventRequestHandler::class] = $app->share(
            fn (Application $app) => new ImportEventRequestHandler(
                $app['event_importer'],
                $app['uuid_generator'],
                $app['event_iri_generator'],
                $app['place_iri_generator'],
                new CombinedRequestBodyParser(
                    new ImportTermRequestBodyParser(new EventCategoryResolver()),
                    new ImportPriceInfoRequestBodyParser($app['config']['base_price_translations'])
                )
            )
        );

        $app[UpdateLocationRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateLocationRequestHandler(
                $app['event_command_bus'],
                $app['place_jsonld_repository']
            )
        );

        $app[UpdateSubEventsRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateSubEventsRequestHandler($app['event_command_bus'])
        );

        $app[UpdateThemeRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateThemeRequestHandler($app['event_command_bus'])
        );

        $app[DeleteThemeRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteThemeRequestHandler($app['event_command_bus'])
        );

        $app[UpdateAudienceRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateAudienceRequestHandler($app['event_command_bus'])
        );

        $app[CopyEventRequestHandler::class] = $app->share(
            fn (Application $app) => new CopyEventRequestHandler(
                $app['event_command_bus'],
                new UuidFactory(),
                $app['event_iri_generator']
            )
        );

        $app[UpdateMajorInfoRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateMajorInfoRequestHandler($app['event_command_bus'])
        );
    }

    public function boot(Application $app): void
    {
    }
}
