<?php

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use CultuurNet\UDB3\Http\Productions\CreateProductionValidator;
use CultuurNet\UDB3\Http\Productions\ProductionsWriteController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ProductionControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app[ProductionsWriteController::class] = $app->share(
            function (Application $app) {
                return new ProductionsWriteController(
                    $app['event_command_bus'],
                    new CreateProductionValidator()
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->post('/', ProductionsWriteController::class . ':create');
        $controllers->put('/{productionId}/events/{eventId}', ProductionsWriteController::class . ':addEventToProduction');
        $controllers->delete('/{productionId}/events/{eventId}', ProductionsWriteController::class . ':removeEventFromProduction');
        $controllers->post('/{productionId}/merge/{fromProductionId}', ProductionsWriteController::class . ':mergeProductions');

        return $controllers;
    }
}
