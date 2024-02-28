<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Ownership\CommandHandlers\RequestOwnershipHandler;

final class OwnershipCommandHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RequestOwnershipHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            RequestOwnershipHandler::class,
            fn () => new RequestOwnershipHandler(
                $container->get(OwnershipRepository::class)
            )
        );
    }
}
