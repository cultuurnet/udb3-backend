<?php

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\Services\LocalRoleReadingService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoleReadingServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['role_reading_service'] = $app->share(
            function ($app) {
                return new LocalRoleReadingService(
                    $app['role_read_repository'],
                    $app['real_role_repository'],
                    $app['role_iri_generator'],
                    $app['role_permissions_read_repository'],
                    $app['user_roles_repository']
                );
            }
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
    }
}
