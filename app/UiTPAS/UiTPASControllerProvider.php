<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPAS;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UiTPASControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/labels',
            function (Application $app) {
                return new JsonResponse(
                    $app['config']['uitpas']['labels']
                );
            }
        );

        return $controllers;
    }
}
