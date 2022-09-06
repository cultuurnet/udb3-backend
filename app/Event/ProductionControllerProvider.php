<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Http\Productions\AddEventToProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\CreateProductionValidator;
use CultuurNet\UDB3\Http\Productions\MergeProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\RemoveEventFromProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionRequestHandler;
use CultuurNet\UDB3\Http\Productions\RenameProductionValidator;
use CultuurNet\UDB3\Http\Productions\SearchProductionsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SkipEventsRequestHandler;
use CultuurNet\UDB3\Http\Productions\SkipEventsValidator;
use CultuurNet\UDB3\Http\Productions\SuggestProductionRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class ProductionControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[SearchProductionsRequestHandler::class] = $app->share(
            fn (Application $app) => new SearchProductionsRequestHandler($app[ProductionRepository::class])
        );

        $app[SuggestProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new SuggestProductionRequestHandler(
                $app[SimilarEventsRepository::class],
                $app['event_jsonld_repository']
            )
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

        $app[RemoveEventFromProductionRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveEventFromProductionRequestHandler($app['event_command_bus'])
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

        $controllers->get('/', SearchProductionsRequestHandler::class);

        $controllers->post('/', CreateProductionRequestHandler::class);
        $controllers->put('/{productionId}/events/{eventId}/', AddEventToProductionRequestHandler::class);
        $controllers->delete('/{productionId}/events/{eventId}/', RemoveEventFromProductionRequestHandler::class);
        $controllers->post('/{productionId}/merge/{fromProductionId}/', MergeProductionsRequestHandler::class);
        $controllers->put('/{productionId}/name/', RenameProductionRequestHandler::class);

        $controllers->post('/skip/', SkipEventsRequestHandler::class);

        $controllers->get('/suggestion/', SuggestProductionRequestHandler::class);

        return $controllers;
    }
}
