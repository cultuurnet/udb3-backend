<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Jobs;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Jobs\GetJobStatusRequestHandler;
use CultuurNet\UDB3\Http\Jobs\ResqueJobStatusFactory;

final class JobsServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [GetJobStatusRequestHandler::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetJobStatusRequestHandler::class,
            fn () => new GetJobStatusRequestHandler(new ResqueJobStatusFactory())
        );
    }
}
