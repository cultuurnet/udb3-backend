<?php

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\OrganizerCardSystemsController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UiTPASServiceOrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['uitpas.organizer_card_systems_controller'] = $app->share(
            function (Application $app) {
                return new OrganizerCardSystemsController(
                    $app['uitpas']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/{organizerId}/cardSystems/',
            'uitpas.organizer_card_systems_controller:get'
        );

        return $controllers;
    }
}
