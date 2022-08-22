<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Http\Productions\AddEventToProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionValidator;
use CultuurNet\UDB3\Http\Productions\MergeProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\ProductionsSearchController;
use CultuurNet\UDB3\Http\Productions\ProductionSuggestionController;
use CultuurNet\UDB3\Http\Productions\RemoveEventRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionValidator;
use CultuurNet\UDB3\Http\Productions\SkipEventsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SkipEventsValidator;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ProductionControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
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

        $app[AddEventToProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new AddEventToProductionRequestHandler($app['event_command_bus'])
        );

        $app[RemoveEventRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveEventRequestHandler($app['event_command_bus'])
        );

        $app[MergeProductionsRequestHandler::class] = $app->share(
            fn (Application $app) => new MergeProductionsRequestHandler($app['event_command_bus'])
        );

        $app[RenameProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new RenameProductionRequestHandler(
                $app['event_command_bus'],
                new RenameProductionValidator()
            )
        );

        $app[SkipEventsRequestHandler::class] = $app->share(
            fn (Application $app) => new SkipEventsRequestHandler(
                $app['event_command_bus'],
                new SkipEventsValidator()
            )
        );


        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', ProductionsSearchController::class . ':search');

        $controllers->post('/', CreateProductionRequestHandler::class);
        $controllers->put('/{productionId}/events/{eventId}/', AddEventToProductionRequestHandler::class);
        $controllers->delete('/{productionId}/events/{eventId}/', RemoveEventRequestHandler::class);
        $controllers->post('/{productionId}/merge/{fromProductionId}/', MergeProductionsRequestHandler::class);
        $controllers->put('/{productionId}/name/', RenameProductionRequestHandler::class);

        $controllers->post('/skip/', SkipEventsRequestHandler::class);

        $controllers->get('/suggestion/', ProductionSuggestionController::class . ':nextSuggestion');

        return $controllers;
    }
}
