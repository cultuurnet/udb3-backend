<?php

namespace CultuurNet\UDB3\Silex\Moderation;

use CultuurNet\UDB3\Search\PullParsingSearchService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ModerationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['moderation_search_service'] = $app->share(
            function ($app) {
                /** @var PullParsingSearchService $search */
                $search = $app['search_service'];

                $moderationSearch = $search->doNotIncludePrivateItems();

                return $moderationSearch;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
