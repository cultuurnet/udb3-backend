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
                    $app['organizer_service']
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

        $controllers->post('/', 'organizer_edit_controller:create');

        $controllers
            ->get('/{cdbid}', 'organizer_controller:get')
            ->bind('organizer');

        $controllers->delete('/{cdbid}', 'organizer_edit_controller:delete');

        $controllers->put(
            '/{organizerId}/url',
            'organizer_edit_controller:updateUrl'
        );

        $controllers->put(
            '/{organizerId}/name',
            'organizer_edit_controller:updateNameDeprecated'
        );

        $controllers->put(
            '/{organizerId}/name/{lang}',
            'organizer_edit_controller:updateName'
        );

        $controllers->put(
            '/{organizerId}/address',
            'organizer_edit_controller:updateAddress'
        );

        $controllers->put(
            '/{organizerId}/contactPoint',
            'organizer_edit_controller:updateContactPoint'
        );

        $controllers->put(
            '/{organizerId}/labels/{labelName}',
            'organizer_edit_controller:addLabel'
        );

        $controllers->delete(
            '{organizerId}/labels/{labelName}',
            'organizer_edit_controller:removeLabel'
        );

        return $controllers;
    }
}
