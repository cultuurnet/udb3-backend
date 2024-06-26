<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Ownership\CommandHandlers\ApproveOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\DeleteOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\RejectOwnershipHandler;
use CultuurNet\UDB3\Ownership\CommandHandlers\RequestOwnershipHandler;

final class OwnershipCommandHandlerProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RequestOwnershipHandler::class,
            ApproveOwnershipHandler::class,
            RejectOwnershipHandler::class,
            DeleteOwnershipHandler::class,
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

        $container->addShared(
            ApproveOwnershipHandler::class,
            fn () => new ApproveOwnershipHandler(
                $container->get(OwnershipRepository::class)
            )
        );

        $container->addShared(
            RejectOwnershipHandler::class,
            fn () => new RejectOwnershipHandler(
                $container->get(OwnershipRepository::class)
            )
        );

        $container->addShared(
            DeleteOwnershipHandler::class,
            fn () => new DeleteOwnershipHandler(
                $container->get(OwnershipRepository::class)
            )
        );
    }
}
