<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Http\Productions\AddEventRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionValidator;
use CultuurNet\UDB3\Http\Productions\ProductionsSearchController;
use CultuurNet\UDB3\Http\Productions\ProductionSuggestionController;
use CultuurNet\UDB3\Http\Productions\ProductionsWriteController;
use CultuurNet\UDB3\Http\Productions\RemoveEventRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionValidator;
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
                    new SkipEventsValidator(),
                    new RenameProductionValidator()
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
                return new ProductionSuggestionController(
                    $app[SimilarEventsRepository::class],
                    $app['event_jsonld_repository']
                );
            }
        );

        $app[CreateProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new CreateProductionRequestHandler(
                $app['event_command_bus'],
                new CreateProductionValidator()
            )
        );

        $app[CreateProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new CreateProductionRequestHandler(
                $app['event_command_bus'],
                new CreateProductionValidator()
            )
        );

        $app[AddEventRequestHandler::class] = $app->share(
            fn (Application $app) => new AddEventRequestHandler($app['event_command_bus'])
        );

        $app[RemoveEventRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveEventRequestHandler($app['event_command_bus'])
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', ProductionsSearchController::class . ':search');

        $controllers->post('/', CreateProductionRequestHandler::class);
        $controllers->put('/{productionId}/events/{eventId}/', AddEventRequestHandler::class);
        $controllers->delete('/{productionId}/events/{eventId}/', RemoveEventRequestHandler::class);
        $controllers->post('/{productionId}/merge/{fromProductionId}/', ProductionsWriteController::class . ':mergeProductions');
        $controllers->put('/{productionId}/name/', ProductionsWriteController::class . ':renameProduction');

        $controllers->post('/skip/', ProductionsWriteController::class . ':skipEvents');

        $controllers->get('/suggestion/', ProductionSuggestionController::class . ':nextSuggestion');

        return $controllers;
    }
}
