<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Broadway\EventStore\DBALEventStore;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository;
use CultuurNet\UDB3\Variations\Command\EventVariationCommandHandler;
use CultuurNet\UDB3\Variations\DefaultEventVariationService;
use CultuurNet\UDB3\Variations\EventVariationRepository;
use CultuurNet\UDB3\Variations\ReadModel\Search\Doctrine\DBALRepository;
use CultuurNet\UDB3\Variations\ReadModel\Search\Doctrine\ExpressionFactory;
use CultuurNet\UDB3\Variations\ReadModel\Search\Projector;
use Silex\Application;
use Silex\ServiceProviderInterface;

class VariationsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['variations'] = $app->share(
            function (Application $app) {
                return new DefaultEventVariationService(
                    $app['variations.repository'],
                    new Version4Generator()
                );
            }
        );

        $app['variations.repository'] = $app->share(
            function (Application $app) {
                return new EventVariationRepository(
                    $app['variations.event_store'],
                    $app['event_bus'],
                    [
                        $app['event_stream_metadata_enricher']
                    ]
                );
            }
        );

        $app['variations.command_handler'] = $app->share(
            function (Application $app) {
                return new EventVariationCommandHandler(
                    $app['variations']
                );
            }
        );

        $app['variations.event_store'] = $app->share(
            function ($app) {
                return new DBALEventStore(
                    $app['dbal_connection'],
                    $app['eventstore_payload_serializer'],
                    new SimpleInterfaceSerializer(),
                    'variations'
                );
            }
        );

        $app['variations.search.projector'] = $app->share(
            function (Application $app) {
                return new Projector(
                    $app['variations.search']
                );
            }
        );

        $app['variations.search'] = $app->share(
            function (Application $app) {
                return new DBALRepository(
                    $app['dbal_connection'],
                    new ExpressionFactory()
                );
            }
        );

        $app['variations.jsonld'] = $app->share(
            function (Application $app) {
                return new CacheDocumentRepository(
                    $app['cache']('variation_jsonld')
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}
