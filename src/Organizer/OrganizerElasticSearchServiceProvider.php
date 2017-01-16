<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchDocumentRepository;
use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchOrganizerSearchService;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class OrganizerElasticSearchServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['organizer_elasticsearch_service'] = $app->share(
            function (Application $app) {
                return new ElasticSearchOrganizerSearchService(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.organizer.index_name']),
                    new StringLiteral($app['elasticsearch.organizer.document_type'])
                );
            }
        );

        $app['organizer_elasticsearch_repository'] = $app->share(
            function (Application $app) {
                return new ElasticSearchDocumentRepository(
                    $app['elasticsearch_client'],
                    new StringLiteral($app['elasticsearch.organizer.index_name']),
                    new StringLiteral($app['elasticsearch.organizer.document_type'])
                );
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
