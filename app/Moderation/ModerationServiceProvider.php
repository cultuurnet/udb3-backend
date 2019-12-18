<?php

namespace CultuurNet\UDB3\Silex\Moderation;

use CultuurNet\UDB3\Moderation\Sapi3\NeedsModerationNarrower;
use CultuurNet\UDB3\Search\Narrowing\QueryNarrowingSearchService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ModerationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['moderation_search_service'] = $app->share(
            function ($app) {
                $search = $app['sapi3_search_service'];

                return new QueryNarrowingSearchService(
                    $search,
                    new NeedsModerationNarrower()
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
