<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPASService;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASLabelsRequestHandler;

final class UiTPASServiceLabelsServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [GetUiTPASLabelsRequestHandler::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetUiTPASLabelsRequestHandler::class,
            function () use ($container) {
                return new GetUiTPASLabelsRequestHandler(
                    $container->get('config')['uitpas']['labels']
                );
            }
        );
    }
}
