<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\FeatureToggles;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FeatureTogglesControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/toggles', function (Application $app) {
            /** @var \Qandidate\Toggle\ToggleManager $toggles */
            $toggles = $app['toggles'];

            $toggleStates = [];

            /** @var \Qandidate\Toggle\Toggle $toggle */
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
