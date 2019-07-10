<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Search\Cache\CacheManager;
use Knp\Command\Command;

abstract class AbstractSearchCacheCommand extends Command
{
    /**
     * @return CacheManager
     */
    protected function getCacheManager()
    {
        $app = $this->getSilexApplication();
        return $app['search_cache_manager'];
    }
}
