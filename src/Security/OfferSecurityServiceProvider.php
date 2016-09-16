<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Offer\ReadModel\Permission\CombinedPermissionQuery;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\Security;
use CultuurNet\UDB3\Offer\Security\SecurityWithLabelPrivacy;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class OfferSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['offer.security'] = $app->share(
            function ($app) {
                $security = new Security(
                    $this->createUserIdentification($app),
                    $this->createPermissionQuery($app),
                    $this->createUserPermissionMatcher($app)
                );

                return new SecurityWithLabelPrivacy(
                    $security,
                    $this->createUserIdentification($app),
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
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

    /**
     * @param Application $app
     * @return UserIdentificationInterface
     */
    private function createUserIdentification(Application $app)
    {
        return new CultureFeedUserIdentification(
            $app['current_user'],
            $app['config']['user_permissions']
        );
    }

    /**
     * @param Application $app
     * @return PermissionQueryInterface
     */
    private function createPermissionQuery(Application $app)
    {
        return new CombinedPermissionQuery(
            [
                $app['event_permission.repository'],
                $app['place_permission.repository'],
            ]
        );
    }

    /**
     * @param Application $app
     * @return UserPermissionMatcher
     */
    private function createUserPermissionMatcher(Application $app)
    {
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
}
