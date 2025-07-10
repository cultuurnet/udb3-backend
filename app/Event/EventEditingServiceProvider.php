<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Place\CanonicalPlaceRepository;

final class EventEditingServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            EventOrganizerRelationService::class,
            RelocateEventToCanonicalPlace::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            EventOrganizerRelationService::class,
            function () use ($container): EventOrganizerRelationService {
                return new EventOrganizerRelationService(
                    $container->get('event_command_bus'),
                    $container->get(EventRelationsRepository::class),
                );
            }
        );

        $container->addShared(
            RelocateEventToCanonicalPlace::class,
            function () use ($container): ReplayFilteringEventListener {
                return new ReplayFilteringEventListener(
                    new RelocateEventToCanonicalPlace(
                        $container->get('event_command_bus'),
                        new CanonicalPlaceRepository($container->get('duplicate_place_repository'))
                    )
                );
            }
        );
    }
}
