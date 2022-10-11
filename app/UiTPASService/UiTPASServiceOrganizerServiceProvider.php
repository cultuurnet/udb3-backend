<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromOrganizerRequestHandler;

final class UiTPASServiceOrganizerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [GetCardSystemsFromOrganizerRequestHandler::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetCardSystemsFromOrganizerRequestHandler::class,
            function () use ($container) {
                return new GetCardSystemsFromOrganizerRequestHandler($container->get('uitpas'));
            }
        );
    }
}
