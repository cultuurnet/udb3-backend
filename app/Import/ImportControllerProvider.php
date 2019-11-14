<?php

namespace CultuurNet\UDB3\Silex\Import;

use CultuurNet\UDB3\Http\Import\ImportRestController;
use CultuurNet\UDB3\Silex\ApiName;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

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

        $app['place_import_controller'] = $app->share(
            function (Application $app) {
                return new ImportRestController(
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['place_importer'],
                    $app['uuid_generator'],
                    $app['place_iri_generator']
                );
            }
        );

        $app['organizer_import_controller'] = $app->share(
            function (Application $app) {
                return new ImportRestController(
                    $app['auth.api_key_reader'],
                    $app['auth.consumer_repository'],
                    $app['organizer_importer'],
                    $app['uuid_generator'],
                    $app['organizer_iri_generator']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/events/', 'event_import_controller:importWithoutId');
        $controllers->put('/events/{cdbid}', 'event_import_controller:importWithId');

        $controllers->post('/places/', 'place_import_controller:importWithoutId');
        $controllers->put('/places/{cdbid}', 'place_import_controller:importWithId');

        $controllers->post('/organizers/', 'organizer_import_controller:importWithoutId');
        $controllers->put('/organizers/{cdbid}', 'organizer_import_controller:importWithId');

        return $controllers;
    }
}
