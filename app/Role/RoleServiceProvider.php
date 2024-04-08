<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator;
use CultuurNet\UDB3\Role\ReadModel\Detail\EventFactory;
use CultuurNet\UDB3\Role\ReadModel\Detail\Projector;
use CultuurNet\UDB3\Role\ReadModel\Labels\LabelRolesProjector;
use CultuurNet\UDB3\Role\ReadModel\Labels\RoleLabelsProjector;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\DBALRepository;
use CultuurNet\UDB3\Role\ReadModel\Users\RoleUsersProjector;
use CultuurNet\UDB3\Role\ReadModel\Users\UserRolesProjector;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;

final class RoleServiceProvider extends AbstractServiceProvider
{
    public const ROLE_SEARCH_V3_REPOSITORY_TABLE_NAME = 'roles_search_v3';

    protected function getProvidedServiceNames(): array
    {
        return [
            'role_iri_generator',
            'role_store',
            'real_role_repository',
            'role_read_repository',
            'user_roles_repository',
            'role_search_v3_repository',
            'role_search_v3_projector',
            'role_detail_projector',
            'user_roles_projector',
            'role_labels_read_repository',
            'role_labels_projector',
            'label_roles_read_repository',
            'label_roles_projector',
            'role_users_read_repository',
            'role_users_projector',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'role_iri_generator',
            fn () => new CallableIriGenerator(
                fn ($roleId) => $container->get('config')['url'] . '/roles/' . $roleId
            )
        );

        $container->addShared(
            'role_store',
            fn () => $container->get('event_store_factory')(AggregateType::role())
        );

        $container->addShared(
            'real_role_repository',
            fn () => new RoleRepository(
                $container->get('role_store'),
                $container->get(EventBus::class),
                [
                    $container->get('event_stream_metadata_enricher'),
                ]
            )
        );

        $container->addShared(
            'role_read_repository',
            fn () => new BroadcastingDocumentRepositoryDecorator(
                new CacheDocumentRepository(
                    $container->get('cache')('role_detail')
                ),
                $container->get(EventBus::class),
                new EventFactory()
            )
        );

        $container->addShared(
            'user_roles_repository',
            fn () => new CacheDocumentRepository($container->get('cache')('user_roles'))
        );

        $container->addShared(
            'role_search_v3_repository',
            fn () => new DBALRepository(
                $container->get('dbal_connection'),
                self::ROLE_SEARCH_V3_REPOSITORY_TABLE_NAME
            )
        );

        $container->addShared(
            'role_search_v3_projector',
            fn () => new ReadModel\Search\Projector($container->get('role_search_v3_repository'))
        );

        $container->addShared(
            'role_detail_projector',
            fn () => new Projector($container->get('role_read_repository'))
        );

        $container->addShared(
            'user_roles_projector',
            fn () => new UserRolesProjector(
                $container->get('user_roles_repository'),
                $container->get('role_read_repository'),
                $container->get('role_users_read_repository'),
            )
        );

        $container->addShared(
            'role_labels_read_repository',
            fn () => new CacheDocumentRepository($container->get('cache')('role_labels'))
        );

        $container->addShared(
            'role_labels_projector',
            fn () => new RoleLabelsProjector(
                $container->get('role_labels_read_repository'),
                $container->get('labels.json_read_repository'),
                $container->get('label_roles_read_repository'),
            )
        );

        $container->addShared(
            'label_roles_read_repository',
            fn () => new CacheDocumentRepository($container->get('cache')('label_roles'))
        );

        $container->addShared(
            'label_roles_projector',
            fn () => new LabelRolesProjector($container->get('label_roles_read_repository'))
        );

        $container->addShared(
            'role_users_read_repository',
            fn () => new CacheDocumentRepository($container->get('cache')('role_users'))
        );

        $container->addShared(
            'role_users_projector',
            fn () => new RoleUsersProjector(
                $container->get('role_users_read_repository'),
                $container->get(Auth0UserIdentityResolver::class),
            )
        );
    }
}
