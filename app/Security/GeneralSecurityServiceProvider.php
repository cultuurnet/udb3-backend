<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * Provides general security services usable by other services.
 */
class GeneralSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['god_user_voter'] = $app->share(
            function (Application $app) {
                return new GodUserVoter(
                    $app['config']['user_permissions']['allow_all']
                );
            }
        );

        $app['user_constraints_read_repository'] = $app->share(
            function (Application $app) {
                return new UserConstraintsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE),
                    new StringLiteral(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE),
                    $app['role_search_v3_repository.table_name']
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
