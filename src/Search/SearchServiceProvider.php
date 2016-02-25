<?php

namespace CultuurNet\UDB3\Silex\Search;

use CultuurNet\UDB3\Search\ResultsGenerator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['search_results_generator'] = $app->share(
            function (Application $app) {
                return new ResultsGenerator(
                    $app['search_service']
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
