<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASLabelsRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

final class UiTPASServiceLabelsControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', GetUiTPASLabelsRequestHandler::class);

        return $controllers;
    }

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
