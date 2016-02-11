<?php

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\LabelJSONDeserializer;
use CultuurNet\UDB3\Symfony\Offer\EditOfferRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class OfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['event_offer_controller'] = $app->share(
            function (Application $app) {
                return new EditOfferRestController(
                    $app['event_editor_with_label_memory'],
                    new LabelJSONDeserializer()
                );
            }
        );

        $app['place_offer_controller'] = $app->share(
            function (Application $app) {
                return new EditOfferRestController(
                    $app['place_editing_service_with_label_memory'],
                    new LabelJSONDeserializer()
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->post(
                'event/{cdbid}/labels',
                'event_offer_controller:addLabel'
            );

        $controllers
            ->delete(
                'event/{cdbid}/labels/{label}',
                'event_offer_controller:removeLabel'
            );

        $controllers
            ->post(
                'place/{cdbid}/labels',
                'place_offer_controller:addlabel'
            );

        $controllers
            ->delete(
                'place/{cdbid}/labels/{label}',
                'place_offer_controller:removeLabel'
            );

        return $controllers;
    }
}
