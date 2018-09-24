<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Organizer\SearchAPI2\IsOrganizerActor;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\SearchAPI2\FilteredSearchService;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_search_api_2'] = $app->share(
            function (Application $app) {
                $search = new FilteredSearchService($app['search_api_2']);
                $search->filter(new IsOrganizerActor());

                return $search;
            }
        );

        $app['organizer_user_permission_matcher'] = $app->share(
            function (Application $app) {
                $userConstraintReadRepository = new UserConstraintsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral('user_roles'),
                    new StringLiteral('role_permissions'),
                    new StringLiteral('roles_search')
                );

                // Note that the current ResultSetPullParser will not actually
                // parse organizer actors, as it only supports events and
                // places. However, it correctly returns the total item count,
                // which is the only thing that is checked by the
                // UserPermissionMatcher.
                $resultSetParser = new ResultSetPullParser(
                    new \XMLReader(),
                    $app['event_iri_generator'],
                    $app['place_iri_generator']
                );

                return new UserPermissionMatcher(
                    $userConstraintReadRepository,
                    new SearchQueryFactory(),
                    $app['organizer_search_api_2'],
                    $resultSetParser
                );
            }
        );

        $app['organizer_permission_voter_inner'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    new OwnerVoter($app['organizer_permission.repository']),
                    new RoleConstraintVoter($app['organizer_user_permission_matcher'])
                );
            }
        );

        $app['organizer_permission_voter'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    $app['god_user_voter'],
                    $app['organizer_permission_voter_inner']
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
