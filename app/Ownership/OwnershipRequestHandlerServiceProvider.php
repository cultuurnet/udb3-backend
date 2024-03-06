<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Ownership\GetOwnershipRequestHandler;
use CultuurNet\UDB3\Http\Ownership\RequestOwnershipRequestHandler;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\User\CurrentUser;
use Ramsey\Uuid\UuidFactory;

final class OwnershipRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RequestOwnershipRequestHandler::class,
            GetOwnershipRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            RequestOwnershipRequestHandler::class,
            fn () => new RequestOwnershipRequestHandler(
                $container->get('event_command_bus'),
                new UuidFactory(),
                $container->get(CurrentUser::class),
                $container->get(OwnershipSearchRepository::class)
            )
        );

        $container->addShared(
            GetOwnershipRequestHandler::class,
            fn () => new GetOwnershipRequestHandler(
                $container->get(OwnershipServiceProvider::OWNERSHIP_JSONLD_REPOSITORY)
            )
        );
    }
}
