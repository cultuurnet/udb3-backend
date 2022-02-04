<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Import;

use CultuurNet\UDB3\Http\Import\ImportLabelVisibilityRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportRestController;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Organizer\ImportOrganizerRequestHandler;
use CultuurNet\UDB3\Http\Place\ImportPlaceRequestHandler;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Model\Import\Validation\Place\PlaceImportValidator;
use CultuurNet\UDB3\Model\Place\PlaceIDParser;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ImportControllerProvider implements ControllerProviderInterface
{
    public const PATH = '/imports';

    public function connect(Application $app)
    {
        $app['event_import_controller'] = $app->share(
            function (Application $app) {
                return new ImportRestController(
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['event_importer'],
                    $app['uuid_generator'],
                    $app['event_iri_generator']
                );
            }
        );

        $app[ImportPlaceRequestHandler::class] = $app->share(
            fn (Application $application) => new ImportPlaceRequestHandler(
                $app['place_repository'],
                $app['uuid_generator'],
                new PlaceDenormalizer(
                    new PlaceImportValidator(
                        new PlaceIDParser(),
                        $app['current_user_id'],
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                ),
                new CombinedRequestBodyParser(
                    new ImportLabelVisibilityRequestBodyParser(
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    ),
                    new ImportTermRequestBodyParser(
                        new PlaceCategoryResolver()
                    )
                ),
                $app['place_iri_generator'],
                $app['imports_command_bus'],
                $app['import_image_collection_factory'],
                $app['labels.labels_locked_for_import_repository'],
                $app['should_auto_approve_new_offer'],
                $app['auth.api_key_reader'],
                $app['auth.consumer_repository']
            )
        );

        $app[ImportOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new ImportOrganizerRequestHandler(
                $app['organizer_repository'],
                $app['imports_command_bus'],
                $app['labels.labels_locked_for_import_repository'],
                $app['uuid_generator'],
                $app['organizer_iri_generator'],
                new CombinedRequestBodyParser(
                    new ImportLabelVisibilityRequestBodyParser(
                        $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                        $app[LabelServiceProvider::RELATIONS_READ_REPOSITORY]
                    )
                )
            )
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/events/', 'event_import_controller:importWithoutId');
        $controllers->put('/events/{cdbid}/', 'event_import_controller:importWithId');

        $controllers->post('/places/', ImportPlaceRequestHandler::class);
        $controllers->put('/places/{placeId}/', ImportPlaceRequestHandler::class);

        $controllers->post('/organizers/', ImportOrganizerRequestHandler::class);
        $controllers->put('/organizers/{organizerId}/', ImportOrganizerRequestHandler::class);

        return $controllers;
    }
}
