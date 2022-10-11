<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\UiTPASService\Controller\AddCardSystemToEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\DeleteCardSystemFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASDetailRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\SetCardSystemsOnEventRequestHandler;

final class UiTPASServiceEventServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GetUiTPASDetailRequestHandler::class,
            GetCardSystemsFromEventRequestHandler::class,
            SetCardSystemsOnEventRequestHandler::class,
            AddCardSystemToEventRequestHandler::class,
            DeleteCardSystemFromEventRequestHandler::class,
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
                return new GetCardSystemsFromEventRequestHandler($container->get('uitpas'));
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
                return new AddCardSystemToEventRequestHandler($container->get('uitpas'));
            }
        );

        $container->addShared(
            DeleteCardSystemFromEventRequestHandler::class,
            function () use ($container) {
                return new DeleteCardSystemFromEventRequestHandler($container->get('uitpas'));
            }
        );
    }
}
