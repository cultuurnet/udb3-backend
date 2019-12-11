<?php

namespace CultuurNet\UDB3\Silex\Moderation;

use CultuurNet\UDB3\Moderation\Sapi2\NeedsModerationNarrower as Sapi2NeedsModerationNarrower;
use CultuurNet\UDB3\Moderation\Sapi3\NeedsModerationNarrower;
use CultuurNet\UDB3\Search\Narrowing\QueryNarrowingSearchService;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ModerationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['moderation_search_service'] = $app->share(
            function ($app) {
                /** @var PullParsingSearchService $search */
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
