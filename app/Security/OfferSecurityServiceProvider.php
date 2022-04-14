<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Security\ResourceOwner\CombinedResourceOwnerQuery;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use CultuurNet\UDB3\Silex\ApiName;
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
        $app['offer_owner_query'] = $app->share(
            function (Application $app) {
                return new CombinedResourceOwnerQuery(
                    [
                        $app['event_owner.repository'],
                        $app['place_owner.repository'],
                    ]
                );
            }
        );

        $app['offer_permission_voter'] = $app->share(
            function (Application $app) {
                return new AnyOfVoter(
                    $app['god_user_voter'],
                    new ResourceOwnerVoter(
                        $app['offer_owner_query'],
                        $app['api_name'] !== ApiName::CLI &&
                        isset($app['config']['performance']['resource_owner_cache']) &&
                        $app['config']['performance']['resource_owner_cache']
                    ),
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
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
