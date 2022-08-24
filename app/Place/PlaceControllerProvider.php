<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Place\GetEventsRequestHandler;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use CultuurNet\UDB3\Http\Place\LegacyPlaceRequestBodyParser;
use CultuurNet\UDB3\Http\Place\UpdateAddressRequestHandler;
use CultuurNet\UDB3\Http\Place\UpdateMajorInfoRequestHandler;
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

        $controllers->put('/{placeId}/address/{language}/', UpdateAddressRequestHandler::class);
        $controllers->put('/{cdbid}/booking-info/', 'place_editing_controller:updateBookingInfo');
        $controllers->put('/{cdbid}/contact-point/', 'place_editing_controller:updateContactPoint');
        $controllers->put('/{placeId}/major-info/', UpdateMajorInfoRequestHandler::class);
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
        $controllers->get('/{cdbid}/events/', GetEventsRequestHandler::class);
        $controllers->post('/{itemId}/images/main/', 'place_editing_controller:selectMainImage');
        $controllers->post('/{itemId}/images/{mediaObjectId}/', 'place_editing_controller:updateImage');
        $controllers->post('/{placeId}/address/{language}/', UpdateAddressRequestHandler::class);
        $controllers->post('/{cdbid}/typical-age-range/', 'place_editing_controller:updateTypicalAgeRange');
        $controllers->post('/{placeId}/major-info/', UpdateMajorInfoRequestHandler::class);
        $controllers->post('/{cdbid}/booking-info/', 'place_editing_controller:updateBookingInfo');
        $controllers->post('/{cdbid}/contact-point/', 'place_editing_controller:updateContactPoint');
        $controllers->post('/{cdbid}/organizer/', 'place_editing_controller:updateOrganizerFromJsonBody');

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[GetEventsRequestHandler::class] = $app->share(
            function (Application $app) {
                return new GetEventsRequestHandler(
                    $app[EventRelationsRepository::class],
                );
            }
        );

        $app[UpdateAddressRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateAddressRequestHandler(
                $app['event_command_bus']
            )
        );

        $app[ImportPlaceRequestHandler::class] = $app->share(
            fn (Application $application) => new ImportPlaceRequestHandler(
                $app['place_repository'],
                $app['uuid_generator'],
                new PlaceDenormalizer(),
                new CombinedRequestBodyParser(
                    new LegacyPlaceRequestBodyParser(),
                    RemoveEmptyArraysRequestBodyParser::createForPlaces(),
                    new ImportTermRequestBodyParser(new PlaceCategoryResolver()),
                    new ImportPriceInfoRequestBodyParser($app['config']['base_price_translations']),
                    ImagesPropertyPolyfillRequestBodyParser::createForPlaces(
                        $app['media_object_iri_generator'],
                        $app['media_object_repository']
                    )
                ),
                $app['place_iri_generator'],
                $app['event_command_bus'],
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
