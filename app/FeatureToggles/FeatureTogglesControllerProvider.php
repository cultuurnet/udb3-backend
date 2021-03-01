<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\FeatureToggles;

use Qandidate\Toggle\Toggle;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FeatureTogglesControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/toggles', function (Application $app) {
            /** @var ToggleManager $toggles */
            $toggles = $app['toggles'];

            $toggleStates = [];

            /** @var Toggle $toggle */
            foreach ($toggles->all() as $toggle) {
                $toggleStates[$toggle->getName()] = $toggle->activeFor(
                    $app['toggles.context']
                );
            }

            return new JsonResponse(
                $toggleStates
            );
        });

        return $controllers;
    }
}
