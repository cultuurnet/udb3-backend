<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\Sapi3SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\Sapi3UserPermissionMatcher;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Organizer\SearchAPI2\IsOrganizerActor;
use CultuurNet\UDB3\SearchAPI2\FilteredSearchService;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_user_permission_matcher'] = $app->share(
            function (Application $app) {
                return new Sapi3UserPermissionMatcher(
                    $app['user_constraints_read_repository'],
                    new Sapi3SearchQueryFactory(),
                    $app[Sapi3SearchServiceProvider::ORGANIZERS_COUNTING_SEARCH_SERVICE]
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
