<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\CommandHandlers\CopyEventHandler;
use CultuurNet\UDB3\Event\CommandHandlers\DeleteOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\RemoveThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAttendanceModeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateUiTPASPricesHandler;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;

final class EventCommandHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            UpdateSubEventsHandler::class,
            UpdateThemeHandler::class,
            RemoveThemeHandler::class,
            UpdateAttendanceModeHandler::class,
            UpdateOnlineUrlHandler::class,
            DeleteOnlineUrlHandler::class,
            UpdateAudienceHandler::class,
            UpdateUiTPASPricesHandler::class,
            CopyEventHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            UpdateSubEventsHandler::class,
            function () use ($container): UpdateSubEventsHandler {
                return new UpdateSubEventsHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            UpdateThemeHandler::class,
            function () use ($container): UpdateThemeHandler {
                return new UpdateThemeHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            RemoveThemeHandler::class,
            function () use ($container): RemoveThemeHandler {
                return new RemoveThemeHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            UpdateAttendanceModeHandler::class,
            function () use ($container): UpdateAttendanceModeHandler {
                return new UpdateAttendanceModeHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            UpdateOnlineUrlHandler::class,
            function () use ($container): UpdateOnlineUrlHandler {
                return new UpdateOnlineUrlHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            DeleteOnlineUrlHandler::class,
            function () use ($container): DeleteOnlineUrlHandler {
                return new DeleteOnlineUrlHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            UpdateAudienceHandler::class,
            function () use ($container): UpdateAudienceHandler {
                return new UpdateAudienceHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            UpdateUiTPASPricesHandler::class,
            function () use ($container): UpdateUiTPASPricesHandler {
                return new UpdateUiTPASPricesHandler($container->get('event_repository'));
            }
        );

        $container->addShared(
            CopyEventHandler::class,
            function () use ($container): CopyEventHandler {
                return new CopyEventHandler(
                    $container->get('event_repository'),
                    $container->get(ProductionRepository::class),
                    $container->get('config')['copy_production'] ?? true
                );
            }
        );
    }
}
