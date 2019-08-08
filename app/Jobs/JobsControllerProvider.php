<?php

namespace CultuurNet\UDB3\Silex\Jobs;

use CultuurNet\UDB3\Http\Jobs\ReadRestController;
use CultuurNet\UDB3\Http\Jobs\ResqueJobStatusFactory;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class JobsControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
        if ($this->isJobsEndpointEnabled($app)) {
            $this->setUpReadRestController($app);
            return $this->setUpEndpoints($app['controllers_factory']);
        } else {
            return $app['controllers_factory'];
        }
    }

    /**
     * @param Application $app
     * @return bool
     */
    private function isJobsEndpointEnabled(Application $app)
    {
        /** @var \Qandidate\Toggle\ToggleManager $toggles */
        $toggles = $app['toggles'];
        return $toggles->active('jobs-endpoint', $app['toggles.context']);
    }

    /**
     * @param Application $app
     */
    private function setUpReadRestController(Application $app)
    {
        $app['jobs.read_rest_controller'] = $app->share(
            function (Application $app) {
                return new ReadRestController(new ResqueJobStatusFactory());
            }
        );
    }

    /**
     * @param ControllerCollection $controllers
     * @return ControllerCollection
     */
    private function setUpEndpoints(ControllerCollection $controllers)
    {
        $controllers->get('/{jobId}', 'jobs.read_rest_controller:get');
        return $controllers;
    }
}
