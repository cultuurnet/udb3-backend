<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceRelatedOwnerRepository;

final class EventPermissionServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_owner.repository',
            'event_organizer_owner.repository',
            'event_permission.projector',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_owner.repository',
            function () use ($container): DBALResourceOwnerRepository {
                return new DBALResourceOwnerRepository(
                    'event_permission_readmodel',
                    $container->get('dbal_connection'),
                    'event_id'
                );
            }
        );

        $container->addShared(
            'event_organizer_owner.repository',
            function () use ($container): DBALResourceRelatedOwnerRepository {
                return new DBALResourceRelatedOwnerRepository(
                    'organizer_permission_readmodel',
                    'event_relations',
                    $container->get('dbal_connection'),
                    'event'
                );
            }
        );

        $container->addShared(
            'event_permission.projector',
            function () use ($container): Projector {
                return new Projector(
                    $container->get('event_owner.repository'),
                    $container->get('cdbxml_created_by_resolver'),
                );
            }
        );
    }
}
