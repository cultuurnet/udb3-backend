<?php

namespace CultuurNet\UDB3\Silex;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Qandidate\Toggle\ToggleManager;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\Route;
use Symfony\Component\HttpFoundation\Response;

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
                $problem = new ApiProblem('Feature is disabled on this installation.');
                $problem->setStatus(Response::HTTP_SERVICE_UNAVAILABLE);
                return new ApiProblemJsonResponse($problem);
            };

            $controllers->run($serviceUnavailableController);
        }

        return $controllers;
    }
}
