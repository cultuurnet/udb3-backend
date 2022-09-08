<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Offer;

use CultuurNet\UDB3\Http\Offer\AddLabelToMultipleRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelToQueryRequestHandler;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultipleJSONDeserializer;
use CultuurNet\UDB3\Offer\IriOfferIdentifierJSONDeserializer;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class BulkLabelOfferControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app[AddLabelToQueryRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelToQueryRequestHandler(
                    $app['bulk_label_offer_command_bus']
                );
            }
        );

        $app[AddLabelToMultipleRequestHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelToMultipleRequestHandler(
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

        $controllers->post('/query/labels/', AddLabelToQueryRequestHandler::class);
        $controllers->post('/offers/labels/', AddLabelToMultipleRequestHandler::class);

        return $controllers;
    }
}
