<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
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

final class ProductionServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ProductionRepository::class,
            SimilarEventsRepository::class,
            SkippedSimilarEventsRepository::class,
            ProductionCommandHandler::class,
            SearchProductionsRequestHandler::class,
            SuggestProductionRequestHandler::class,
            CreateProductionRequestHandler::class,
            AddEventToProductionRequestHandler::class,
            RemoveEventFromProductionRequestHandler::class,
            MergeProductionsRequestHandler::class,
            RenameProductionRequestHandler::class,
            SkipEventsRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ProductionRepository::class,
            function () use ($container): BroadcastingProductionRepository {
                return new BroadcastingProductionRepository(
                    new DBALProductionRepository($container->get('dbal_connection')),
                    $container->get(EventBus::class),
                    $container->get(EventJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY),
                );
            }
        );

        $container->addShared(
            SimilarEventsRepository::class,
            function () use ($container): SimilarEventsRepository {
                return new SimilarEventsRepository($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            SkippedSimilarEventsRepository::class,
            function () use ($container): SkippedSimilarEventsRepository {
                return new SkippedSimilarEventsRepository($container->get('dbal_connection'));
            }
        );

        $container->addShared(
            ProductionCommandHandler::class,
            function () use ($container): ProductionCommandHandler {
                return new ProductionCommandHandler(
                    $container->get(ProductionRepository::class),
                    $container->get(SkippedSimilarEventsRepository::class),
                    $container->get('event_jsonld_repository'),
                );
            }
        );

        $container->addShared(
            SearchProductionsRequestHandler::class,
            function () use ($container): SearchProductionsRequestHandler {
                return new SearchProductionsRequestHandler($container->get(ProductionRepository::class));
            }
        );

        $container->addShared(
            SuggestProductionRequestHandler::class,
            function () use ($container): SuggestProductionRequestHandler {
                return new SuggestProductionRequestHandler(
                    $container->get(SimilarEventsRepository::class),
                    $container->get('event_jsonld_repository'),
                );
            }
        );

        $container->addShared(
            CreateProductionRequestHandler::class,
            function () use ($container): CreateProductionRequestHandler {
                return new CreateProductionRequestHandler(
                    $container->get('event_command_bus'),
                    new CreateProductionValidator(),
                );
            }
        );

        $container->addShared(
            AddEventToProductionRequestHandler::class,
            function () use ($container): AddEventToProductionRequestHandler {
                return new AddEventToProductionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            RemoveEventFromProductionRequestHandler::class,
            function () use ($container): RemoveEventFromProductionRequestHandler {
                return new RemoveEventFromProductionRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            MergeProductionsRequestHandler::class,
            function () use ($container): MergeProductionsRequestHandler {
                return new MergeProductionsRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            RenameProductionRequestHandler::class,
            function () use ($container): RenameProductionRequestHandler {
                return new RenameProductionRequestHandler(
                    $container->get('event_command_bus'),
                    new RenameProductionValidator(),
                );
            }
        );

        $container->addShared(
            SkipEventsRequestHandler::class,
            function () use ($container): SkipEventsRequestHandler {
                return new SkipEventsRequestHandler(
                    $container->get('event_command_bus'),
                    new SkipEventsValidator(),
                );
            }
        );
    }
}
