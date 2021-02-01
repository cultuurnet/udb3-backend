<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

final class UiTPASServiceLabelsControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/',
            function (Application $app) {
                return new JsonResponse(
                    $app['config']['uitpas']['labels']
                );
            }
        );

        return $controllers;
    }
}
