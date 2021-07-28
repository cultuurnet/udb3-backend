<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Security\ResourceOwner\CombinedResourceOwnerQuery;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle6\Client;
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
                return new CombinedResourceOwnerQuery(
                    [
                        $app['event_owner.repository'],
                        $app['place_owner.repository'],
                    ]
                );
            }
        );

        $app['offer_permission_voter_inner'] = $app->share(
            function (Application $app) {
                return new AnyOfVoter(
                    new ResourceOwnerVoter($app['offer_permission_query']),
                    new Sapi3RoleConstraintVoter(
                        $app['user_constraints_read_repository'],
                        new Uri($app['config']['search']['v3']['base_url'] . '/offers/'),
                        new Client(new \GuzzleHttp\Client()),
                        $app['config']['search']['v3']['api_key'] ?? null,
                        ['disableDefaultFilters' => true]
                    )
                );
            }
        );

        $app['offer_permission_voter'] = $app->share(
            function (Application $app) {
                return new AnyOfVoter(
                    $app['god_user_voter'],
                    $app['offer_permission_voter_inner']
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
