<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Cultuurkuur;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class CultuurkuurServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GetRegionsRequestHandler::class,
            GetEducationLevelsRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetRegionsRequestHandler::class,
            function () {
                return new GetRegionsRequestHandler();
            }
        );

        $container->addShared(
            GetEducationLevelsRequestHandler::class,
            function () {
                return new GetEducationLevelsRequestHandler();
            }
        );
    }
}
