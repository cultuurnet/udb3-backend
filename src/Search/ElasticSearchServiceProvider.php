<?php

namespace CultuurNet\UDB3\Silex\Search;

use Elasticsearch\ClientBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['elasticsearch_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('elasticsearch');

                $logger->pushHandler(
                    new StreamHandler(__DIR__ . '/../log/elasticsearch.log')
                );

                return $logger;
            }
        );

        $app['elasticsearch_client'] = $app->share(
            function (Application $app) {
                $builder = ClientBuilder::create()
                    ->setHosts(
                        [
                            $app['elasticsearch.host'],
                        ]
                    );

                if (isset($app['elasticsearch.log']) && $app['elasticsearch.log'] === true) {
                    $builder = $builder->setLogger($app['elasticsearch_logger']);
                }

                return $builder->build();
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
