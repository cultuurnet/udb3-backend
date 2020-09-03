<?php

namespace CultuurNet\UDB3\Silex\Event;

use Cake\Chronos\Date;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilaritiesClient;
use CultuurNet\UDB3\Http\Productions\CreateProductionValidator;
use CultuurNet\UDB3\Http\Productions\ProductionsSearchController;
use CultuurNet\UDB3\Http\Productions\ProductionSuggestionController;
use CultuurNet\UDB3\Http\Productions\ProductionsWriteController;
use CultuurNet\UDB3\Http\Productions\SkipEventsValidator;
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
                    new CreateProductionValidator(),
                    new SkipEventsValidator()
                );
            }
        );

        $app[ProductionsSearchController::class] = $app->share(
            function (Application $app) {
                return new ProductionsSearchController(
                    $app[ProductionRepository::class]
                );
            }
        );

        $app[ProductionSuggestionController::class] = $app->share(
            function (Application $app) {
                $minDate = Date::now();
                if (isset($app['config']['event_similarities_api']['suggestions']['min_date'])) {
                    $minDate = Date::createFromFormat('Y-m-d', $app['config']['event_similarities_api']['suggestions']['min_date']);
                }

                return new ProductionSuggestionController(
                    $app[SimilaritiesClient::class],
                    $app['event_jsonld_repository'],
                    $minDate
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/', ProductionsSearchController::class . ':search');
        $controllers->post('/', ProductionsWriteController::class . ':create');
        $controllers->put('/{productionId}/events/{eventId}', ProductionsWriteController::class . ':addEventToProduction');
        $controllers->delete('/{productionId}/events/{eventId}', ProductionsWriteController::class . ':removeEventFromProduction');
        $controllers->post('/{productionId}/merge/{fromProductionId}', ProductionsWriteController::class . ':mergeProductions');

        $controllers->post('/skip', ProductionsWriteController::class . ':skipEvents');

        $controllers->get('/suggestion', ProductionSuggestionController::class . ':nextSuggestion');

        return $controllers;
    }
}
