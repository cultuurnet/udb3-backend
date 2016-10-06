<?php

namespace CultuurNet\UDB3\Silex\Search;

use Elasticsearch\ClientBuilder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['elasticsearch_client'] = $app->share(
            function (Application $app) {
                return ClientBuilder::create()
                    ->setHosts(
                        [
                            $app['elasticsearch.host']
                        ]
                    )
                    ->build();
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
