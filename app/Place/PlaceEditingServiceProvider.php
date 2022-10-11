<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;

final class PlaceEditingServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [PlaceOrganizerRelationService::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            PlaceOrganizerRelationService::class,
            function () use ($container) {
                return new PlaceOrganizerRelationService(
                    $container->get('event_command_bus'),
                    $container->get(PlaceRelationsRepository::class)
                );
            }
        );
    }
}
