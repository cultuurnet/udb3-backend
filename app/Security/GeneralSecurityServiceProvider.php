<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\ValueObject\SapiVersion;
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

        $app['current_user_identification'] = $app->share(
            function (Application $app) {
                return new CultureFeedUserIdentification(
                    $app['current_user'],
                    $app['config']['user_permissions']
                );
            }
        );

        $app['role_constraints_mode'] = $app->share(
            function (Application $app) {
                return SapiVersion::fromNative($app['config']['role_constraints_mode']);
            }
        );

        $app['user_constraints_read_repository.v2'] = $app->share(
            function (Application $app) {
                return new UserConstraintsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral(UserPermissionsServiceProvider::USER_ROLES_TABLE),
                    new StringLiteral(UserPermissionsServiceProvider::ROLE_PERMISSIONS_TABLE),
                    $app['role_search_repository.table_name']
                );
            }
        );

        $app['user_constraints_read_repository.v3'] = $app->share(
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
