<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Role\RoleServiceProvider;
use CultuurNet\UDB3\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Security\Permission\GodUserVoter;

/**
 * Provides general security services usable by other services.
 */
final class GeneralSecurityServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'god_user_voter',
            'user_constraints_read_repository',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'god_user_voter',
            fn () => new GodUserVoter($container->get('config')['user_permissions']['allow_all'])
        );

        $container->addShared(
            'user_constraints_read_repository',
            fn () => new UserConstraintsReadRepository(
                $container->get('dbal_connection'),
                UserPermissionsServiceProvider::USER_ROLES_TABLE,
                UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE,
                RoleServiceProvider::ROLE_SEARCH_V3_REPOSITORY_TABLE_NAME,
            )
        );
    }
}
