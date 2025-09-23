<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Permission\Projector;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceOwnerRepository;
use CultuurNet\UDB3\Security\ResourceOwner\Doctrine\DBALResourceRelatedOwnerRepository;

final class EventPermissionServiceProvider extends AbstractServiceProvider
{
    public const EVENT_OWNER_REPOSITORY = 'event_owner.repository';
    public const EVENT_ORGANIZER_OWNER_REPOSITORY = 'event_organizer_owner.repository';
    public const EVENT_PERMISSION_PROJECTOR = 'event_permission.projector';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::EVENT_OWNER_REPOSITORY,
            self::EVENT_ORGANIZER_OWNER_REPOSITORY,
            self::EVENT_PERMISSION_PROJECTOR,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::EVENT_OWNER_REPOSITORY,
            function () use ($container): DBALResourceOwnerRepository {
                return new DBALResourceOwnerRepository(
                    'event_permission_readmodel',
                    $container->get('dbal_connection'),
                    'event_id'
                );
            }
        );

        $container->addShared(
            self::EVENT_ORGANIZER_OWNER_REPOSITORY,
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
            self::EVENT_PERMISSION_PROJECTOR,
            function () use ($container): Projector {
                return new Projector(
                    $container->get(EventPermissionServiceProvider::EVENT_OWNER_REPOSITORY),
                    $container->get('cdbxml_created_by_resolver'),
                );
            }
        );
    }
}
