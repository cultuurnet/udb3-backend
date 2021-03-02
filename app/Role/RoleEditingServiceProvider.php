<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Role\Services\DefaultRoleEditingService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RoleEditingServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['role_editing_service'] = $app->share(
            function ($app) {
                return new DefaultRoleEditingService(
                    $app['event_command_bus'],
                    new Version4Generator(),
                    $app['real_role_repository']
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
