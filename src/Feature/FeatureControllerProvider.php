<?php

namespace CultuurNet\UDB3\Silex\Feature;

use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Route;

/**
 * Makes routes of a controller provider unavailable based on a feature toggle.
 *
 * When the feature toggle is active, the routes remain unaltered. When the
 * feature toggle is inactive, the routes will respond with a HTTP 503
 * Service Unavailable.
 */
class FeatureControllerProvider implements ControllerProviderInterface
{
    /**
     * @var ControllerProviderInterface
     */
    private $wrapped;

    /**
     * @var string
     */
    private $toggle;

    public function __construct($toggle, ControllerProviderInterface $wrapped)
    {
        $this->toggle = $toggle;
        $this->wrapped = $wrapped;
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection|Route $controllers */
        $controllers = $this->wrapped->connect($app);

        /** @var ToggleManager $toggles */
        $toggles = $app['toggles'];

        if (!$toggles->active($this->toggle, $app['toggles.context'])) {
            $serviceUnavailableController = function () {
                return new FeatureDisabledJsonResponse();
            };

            $controllers->run($serviceUnavailableController);
        }

        return $controllers;
    }
}
