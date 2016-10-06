<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Symfony\Organizer\EditOrganizerRestController;
use CultuurNet\UDB3\Symfony\Organizer\ReadOrganizerRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['organizer_controller'] = $app->share(
            function (Application $app) {
                return new ReadOrganizerRestController(
                    $app['organizer_service'],
                    $app['organizer_lookup'],
                    $app['organizer_website.repository']
                );
            }
        );

        $app['organizer_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditOrganizerRestController(
                    $app['organizer_editing_service'],
                    $app['organizer_iri_generator']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/{cdbid}', 'organizer_controller:get')
            ->bind('organizer');

        $controllers->get(
            '/suggest/{term}',
            'organizer_controller:findByPartOfTitle'
        );

        $controllers->post(
            '/',
            'organizer_edit_controller:create'
        );

        $controllers->delete('/{cdbid}', 'organizer_edit_controller:delete');

        $controllers->get('/', 'organizer_controller:search');

        return $controllers;
    }
}
