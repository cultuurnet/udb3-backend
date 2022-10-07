<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\StringLiteral;

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
            fn() => new GodUserVoter($container->get('config')['user_permissions']['allow_all'])
        );

        $container->addShared(
            'user_constraints_read_repository',
            fn() => new UserConstraintsReadRepository(
                $container->get('dbal_connection'),
                new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE),
                new StringLiteral(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE),
                $container->get('role_search_v3_repository.table_name'),
            )
        );
    }
}
