<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Offer\Commands\AddLabelToMultipleJSONDeserializer;
use CultuurNet\UDB3\Offer\Commands\AddLabelToQueryJSONDeserializer;
use CultuurNet\UDB3\Offer\IriOfferIdentifierJSONDeserializer;
use CultuurNet\UDB3\Http\CommandDeserializerController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class BulkLabelOfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['bulk_label_query_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new AddLabelToQueryJSONDeserializer(),
                    $app['bulk_label_offer_command_bus']
                );
            }
        );

        $app['bulk_label_selection_controller'] = $app->share(
            function (Application $app) {
                return new CommandDeserializerController(
                    new AddLabelToMultipleJSONDeserializer(
                        new IriOfferIdentifierJSONDeserializer(
                            $app['iri_offer_identifier_factory']
                        )
                    ),
                    $app['bulk_label_offer_command_bus']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/query/labels/', 'bulk_label_query_controller:handle');
        $controllers->post('/offers/labels/', 'bulk_label_selection_controller:handle');

        return $controllers;
    }
}
