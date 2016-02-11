<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['event_label_controller'] = $app->share(
            function (Application $app) {
                return new EditOfferRestController(
                    $app['event_editor'],
                    $app['current_user'],
                    $app['used_labels_memory']
                );
            }
        );

        $app['place_label_controller'] = $app->share(
            function (Application $app) {
                return new EditOfferRestController(
                    $app['place_editing_service'],
                    $app['current_user'],
                    $app['used_labels_memory']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->post(
                'event/{cdbid}/labels',
                'event_label_controller:addLabel'
            );

        $controllers
            ->delete(
                'event/{cdbid}/labels/{label}',
                'event_label_controller:removeLabel'
            );

        $controllers
            ->post(
                'place/{cdbid}/labels',
                'place_label_controller:addlabel'
            );

        $controllers
            ->delete(
                'place/{cdbid}/labels/{label}',
                'place_label_controller:removeLabel'
            );

        return $controllers;
    }
}
