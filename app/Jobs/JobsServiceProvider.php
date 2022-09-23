<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Jobs;

use CultuurNet\UDB3\Http\Jobs\GetJobStatusRequestHandler;
use CultuurNet\UDB3\Http\Jobs\ResqueJobStatusFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JobsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[GetJobStatusRequestHandler::class] = $app->share(
            fn () => new GetJobStatusRequestHandler(new ResqueJobStatusFactory())
        );
    }

    public function boot(Application $app): void
    {
    }
}
