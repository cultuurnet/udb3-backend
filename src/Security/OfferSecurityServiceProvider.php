<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\ReadModel\Permission\CombinedPermissionQuery;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\Sapi3SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\Sapi3UserPermissionMatcher;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OfferSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['current_user_identification'] = $app->share(
            function (Application $app) {
                return new CultureFeedUserIdentification(
                    $app['current_user'],
                    $app['config']['user_permissions']
                );
            }
        );

        $app['offer_permission_query'] = $app->share(
            function (Application $app) {
                return new CombinedPermissionQuery(
                    [
                        $app['event_permission.repository'],
                        $app['place_permission.repository'],
                    ]
                );
            }
        );

        $app['role_constraints_mode'] = $app->share(
            function (Application $app) {
                return SapiVersion::fromNative($app['config']['role_constraints_mode']);
            }
        );

        $app['user_permission_matcher'] = $app->share(
            function (Application $app) {
                /** @var SapiVersion $sapiVersion */
                $sapiVersion = $app['role_constraints_mode'];

                return $app['user_permission_matcher.' . $sapiVersion->getValue()];
            }
        );

        $app['user_permission_matcher.v2'] = $app->share(
            function (Application $app) {
                $resultSetParser = new ResultSetPullParser(
                    new \XMLReader(),
                    $app['event_iri_generator'],
                    $app['place_iri_generator']
                );

                return new UserPermissionMatcher(
                    $app['user_constraints_read_repository.v2'],
                    new SearchQueryFactory(),
                    $app['search_api_2'],
                    $resultSetParser
                );
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

        $app['user_permission_matcher.v3'] = $app->share(
            function (Application $app) {
                return new Sapi3UserPermissionMatcher(
                    $app['user_constraints_read_repository.v3'],
                    new Sapi3SearchQueryFactory(),
                    $app['sapi3_search_service']
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

        $app['offer_permission_voter_inner'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    new OwnerVoter($app['offer_permission_query']),
                    new RoleConstraintVoter($app['user_permission_matcher'])
                );
            }
        );

        $app['offer_permission_voter'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    $app['god_user_voter'],
                    $app['offer_permission_voter_inner']
                );
            }
        );

        $app['facility_permission_voter'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    $app['god_user_voter'],
                    new UserPermissionVoter(
                        $app['user_permissions_read_repository']
                    )
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
