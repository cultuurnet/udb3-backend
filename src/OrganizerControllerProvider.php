<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Address;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerEditingServiceInterface;
use CultuurNet\UDB3\Organizer\ReadModel\Lookup\OrganizerLookupServiceInterface;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Symfony\Organizer\OrganizerController;
use CultuurNet\UDB3\Title;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['organizer_controller'] = $app->share(
            function (Application $app) {
                return new OrganizerController(
                    $app['organizer_service'],
                    $app['organizer_lookup'],
                    $app['organizer_editing_service'],
                    $app['organizer_iri_generator']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/organizer/{cdbid}', 'organizer_controller:get')
            ->bind('organizer');

        $controllers->get(
            '/api/1.0/organizer/suggest/{term}',
            'organizer_controller:findByPartOfTitle'
        );

        $controllers->post(
            '/api/1.0/organizer',
            'organizer_controller:createOrganizer'
        );

        return $controllers;
    }
}
