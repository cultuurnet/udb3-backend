<?php

declare(strict_types=1);

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
        $app['jobs.read_rest_controller'] = $app->share(
            function (Application $app) {
                return new ReadRestController(new ResqueJobStatusFactory());
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/{jobId}/', 'jobs.read_rest_controller:get');

        return $controllers;
    }
}
