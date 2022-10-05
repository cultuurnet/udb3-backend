<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Event\Productions\BroadcastingProductionRepository;
use CultuurNet\UDB3\Event\Productions\DBALProductionRepository;
use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Event\Productions\SimilarEventsRepository;
use CultuurNet\UDB3\Event\Productions\SkippedSimilarEventsRepository;
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
use Silex\ServiceProviderInterface;

class ProductionServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app[ProductionRepository::class] = $app->share(
            function ($app) {
                return new BroadcastingProductionRepository(
                    new DBALProductionRepository($app['dbal_connection']),
                    $app[EventBus::class],
                    $app[EventJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY]
                );
            }
        );

        $app[SimilarEventsRepository::class] = $app->share(
            function ($app) {
                return new SimilarEventsRepository($app['dbal_connection']);
            }
        );

        $app[SkippedSimilarEventsRepository::class] = $app->share(
            function ($app) {
                return new SkippedSimilarEventsRepository($app['dbal_connection']);
            }
        );

        $app[ProductionCommandHandler::class] = $app->share(
            function ($app) {
                return new ProductionCommandHandler(
                    $app[ProductionRepository::class],
                    $app[SkippedSimilarEventsRepository::class],
                    $app['event_jsonld_repository']
                );
            }
        );

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
    }

    public function boot(Application $app)
    {
    }
}
