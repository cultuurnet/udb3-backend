<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use CultuurNet\UDB3\Http\Place\LegacyPlaceRequestBodyParser;
use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Place\EditPlaceRestController;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
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

        $controllers->post('/', ImportPlaceRequestHandler::class);
        $controllers->put('/{placeId}', ImportPlaceRequestHandler::class);

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
        $app['place_editing_controller'] = $app->share(
            function (Application $app) {
                return new EditPlaceRestController(
                    $app['place_editing_service'],
                    $app['event_relations_repository'],
                    $app['media_manager']
                );
            }
        );

        $app[ImportPlaceRequestHandler::class] = $app->share(
            fn (Application $application) => new ImportPlaceRequestHandler(
                $app['place_repository'],
                $app['uuid_generator'],
                new PlaceDenormalizer(),
                new CombinedRequestBodyParser(
                    new LegacyPlaceRequestBodyParser(),
                    new ImportTermRequestBodyParser(new PlaceCategoryResolver()),
                    new ImportPriceInfoRequestBodyParser($app['config']['base_price_translations']),
                    ImagesPropertyPolyfillRequestBodyParser::createForPlaces(
                        $app['media_object_iri_generator'],
                        $app['media_object_repository']
                    )
                ),
                $app['place_iri_generator'],
                $app['imports_command_bus'],
                $app['import_image_collection_factory']
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
