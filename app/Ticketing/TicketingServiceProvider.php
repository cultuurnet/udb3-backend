<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ticketing;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Ticketing\Repositories\DBALTicketingRegistrationRepository;
use CultuurNet\UDB3\Ticketing\Repositories\TicketingRegistrationRepository;

final class TicketingServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            TicketingRegistrationRepository::class,
            TicketingRegistrationsProjector::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            TicketingRegistrationRepository::class,
            fn () => new DBALTicketingRegistrationRepository($container->get('dbal_connection'))
        );

        $container->addShared(
            TicketingRegistrationsProjector::class,
            fn () => new TicketingRegistrationsProjector(
                $container->get(TicketingRegistrationRepository::class),
                $container->get('config')['ticketing']['ticketmatic']['reservation_link_hosts'] ?? []
            )
        );
    }
}
