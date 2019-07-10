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
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OfferSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
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

        $app['user_permission_matcher.v3'] = $app->share(
            function (Application $app) {
                return new Sapi3UserPermissionMatcher(
                    $app['user_constraints_read_repository.v3'],
                    new Sapi3SearchQueryFactory(),
                    $app[Sapi3SearchServiceProvider::OFFERS_COUNTING_SEARCH_SERVICE]
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
