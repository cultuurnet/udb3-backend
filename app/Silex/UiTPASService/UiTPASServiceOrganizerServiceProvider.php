<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromOrganizerRequestHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UiTPASServiceOrganizerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[GetCardSystemsFromOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCardSystemsFromOrganizerRequestHandler($app['uitpas'])
        );
    }

    public function boot(Application $app): void
    {
    }
}
