<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Jobs;

use CultuurNet\UDB3\Http\Jobs\GetJobStatusRequestHandler;
use CultuurNet\UDB3\Http\Jobs\ResqueJobStatusFactory;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class JobsControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[GetJobStatusRequestHandler::class] = $app->share(
            fn () => new GetJobStatusRequestHandler(new ResqueJobStatusFactory())
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllers->get('/{jobId}/', GetJobStatusRequestHandler::class);

        return $controllers;
    }
}
