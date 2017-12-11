<?php

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Media\MediaSecurity;
use CultuurNet\UDB3\Offer\ReadModel\Permission\CombinedPermissionQuery;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\SearchQueryFactory;
use CultuurNet\UDB3\Offer\Security\Security;
use CultuurNet\UDB3\Offer\Security\SecurityWithLabelPrivacy;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcher;
use CultuurNet\UDB3\Place\Commands\UpdateFacilities;
use CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine\UserConstraintsReadRepository;
use CultuurNet\UDB3\SearchAPI2\ResultSetPullParser;
use CultuurNet\UDB3\Security\ClassNameCommandFilter;
use CultuurNet\UDB3\Security\CultureFeedUserIdentification;
use CultuurNet\UDB3\Security\Permission\UserPermissionVoter;
use CultuurNet\UDB3\Security\SecurityWithUserPermission;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Qandidate\Toggle\ToggleManager;
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

        $app['offer_permission_voter'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    new GodUserVoter($app['config']['user_permissions']['allow_all']),
                    new OwnerVoter($app['offer_permission_query']),
                    new RoleConstraintVoter($app['user_permission_matcher'])
                );
            }
        );

        $app['facility_permission_voter'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    new GodUserVoter($app['config']['user_permissions']['allow_all']),
                    new UserPermissionVoter(
                        $app['user_permissions_read_repository']
                    )
                );
            }
        );


        $app['offer.security'] = $app->share(
            function ($app) {
                $security = new Security(
                    $app['current_user_identification'],
                    $app['offer_permission_voter']
                );

                $security = new SecurityWithLabelPrivacy(
                    $security,
                    $app['current_user_identification'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );

                $security = new MediaSecurity($security);

                $filterCommands = [];

                /** @var ToggleManager $toggles */
                $toggles = $app['toggles'];
                if ($toggles->active('facility-permission', $app['toggles.context'])) {
                    $filterCommands[] = new StringLiteral(UpdateFacilities::class);
                }

                $security = new SecurityWithUserPermission(
                    $security,
                    $app['current_user_identification'],
                    $app['facility_permission_voter'],
                    new ClassNameCommandFilter(...$filterCommands)
                );

                return $security;
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
