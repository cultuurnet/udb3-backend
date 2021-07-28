<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Security;

use CultuurNet\UDB3\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Security\Permission\Sapi3RoleConstraintVoter;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle6\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerSecurityServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['organizer_permission_voter_inner'] = $app->share(
            function (Application $app) {
                return new CompositeVoter(
                    new OwnerVoter($app['organizer_permission.repository']),
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
