<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use CultuurNet\UDB3\ApiName;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_permission_voter'] = $app->share(
            function (Application $app) {
                return new AnyOfVoter(
                    $app['god_user_voter'],
                    new ResourceOwnerVoter(
                        $app['organizer_owner.repository'],
                        $app['api_name'] !== ApiName::CLI &&
                        isset($app['config']['performance']['resource_owner_cache']) &&
                        $app['config']['performance']['resource_owner_cache']
                    ),
                    new Sapi3RoleConstraintVoter(
                        $app['user_constraints_read_repository'],
                        new Uri($app['config']['search']['v3']['base_url'] . '/organizers/'),
                        new Client(new \GuzzleHttp\Client()),
                        $app['config']['search']['v3']['api_key'] ?? null,
                        []
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
