<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\ReadModel\Permission\CombinedPermissionQuery;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
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

        $app['user_permission_matcher'] = $app->share(
            function (Application $app) {
                $userConstraintReadRepository = new UserConstraintsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral('user_roles'),
                    new StringLiteral('role_permissions'),
                    new StringLiteral('roles_search')
                );

                $resultSetParser = new ResultSetPullParser(
                    new \XMLReader(),
                    $app['event_iri_generator'],
                    $app['place_iri_generator']
                );

                return new UserPermissionMatcher(
                    $userConstraintReadRepository,
                    new SearchQueryFactory(),
                    $app['search_api_2'],
                    $resultSetParser
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
