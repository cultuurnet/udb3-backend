<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\UiTPASService\Controller\AddCardSystemToEventRequestHandler;
use CultuurNet\UDB3\UiTPAS\Client\UiTPASClient;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASDetailRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\LegacyAddCardSystemToEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\LegacyDeleteCardSystemFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\LegacyGetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\SetCardSystemsOnEventRequestHandler;

final class UiTPASServiceEventServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GetUiTPASDetailRequestHandler::class,
            GetCardSystemsFromEventRequestHandler::class,
            LegacyGetCardSystemsFromEventRequestHandler::class,
            SetCardSystemsOnEventRequestHandler::class,
            AddCardSystemToEventRequestHandler::class,
            LegacyAddCardSystemToEventRequestHandler::class,
            LegacyDeleteCardSystemFromEventRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetUiTPASDetailRequestHandler::class,
            function () use ($container) {
                return new GetUiTPASDetailRequestHandler(
                    $container->get('uitpas'),
                    new CallableIriGenerator(
                        fn (string $eventId) => $container->get('config')['url'] . '/uitpas/events' . $eventId
                    ),
                    new CallableIriGenerator(
                        fn (string $eventId) => $container->get('config')['url'] . '/uitpas/events' . $eventId . '/card-systems'
                    )
                );
            }
        );

        $container->addShared(
            GetCardSystemsFromEventRequestHandler::class,
            function () use ($container) {
                return new GetCardSystemsFromEventRequestHandler($container->get(UiTPASClient::class));
            }
        );

        $container->addShared(
            LegacyGetCardSystemsFromEventRequestHandler::class,
            function () use ($container) {
                return new LegacyGetCardSystemsFromEventRequestHandler($container->get('uitpas'));
            }
        );

        $container->addShared(
            SetCardSystemsOnEventRequestHandler::class,
            function () use ($container) {
                return new SetCardSystemsOnEventRequestHandler($container->get('uitpas'));
            }
        );

        $container->addShared(
            AddCardSystemToEventRequestHandler::class,
            function () use ($container) {
                return new AddCardSystemToEventRequestHandler($container->get(UiTPASClient::class));
            }
        );

        $container->addShared(
            LegacyAddCardSystemToEventRequestHandler::class,
            function () use ($container) {
                return new LegacyAddCardSystemToEventRequestHandler($container->get('uitpas'));
            }
        );

        $container->addShared(
            LegacyDeleteCardSystemFromEventRequestHandler::class,
            function () use ($container) {
                return new LegacyDeleteCardSystemFromEventRequestHandler($container->get('uitpas'));
            }
        );
    }
}
