<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASLabelsRequestHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class UiTPASServiceLabelsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[GetUiTPASLabelsRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUiTPASLabelsRequestHandler(
                $app['config']['uitpas']['labels']
            )
        );
    }

    public function boot(Application $app): void
    {
    }
}
