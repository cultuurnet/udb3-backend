<?php

namespace CultuurNet\UDB3\Silex\UiTPAS;

use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\EventProcessManager;
use CultuurNet\UDB3\UiTPAS\Label\HttpUiTPASLabelsRepository;
use Guzzle\Http\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class UiTPASIncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['uitpas_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../../log/uitpas-events.log');
            }
        );

        $app['uitpas_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('uitpas-events');
                $logger->pushHandler($app['uitpas_log_handler']);
                return $logger;
            }
        );

        $app['uitpas_deserializer_locator'] = $app->share(
            function () {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.uitpas-events.event-card-systems-updated+json'
                    ),
                    new EventCardSystemsUpdatedDeserializer()
                );
                return $deserializerLocator;
            }
        );

        $app['uitpas_event_bus_forwarding_consumer_factory'] = $app->share(
            function (Application $app) {
                return new EventBusForwardingConsumerFactory(
                    $app['amqp-execution-delay'],
                    $app['amqp.connection'],
                    $app['uitpas_logger'],
                    $app['uitpas_deserializer_locator'],
                    $app['event_bus'],
                    new StringLiteral($app['config']['amqp']['consumer_tag'])
                );
            }
        );

        $app['amqp.uitpas_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                $consumerConfig = $app['config']['amqp']['consumers']['uitpas'];
                $exchange = new StringLiteral($consumerConfig['exchange']);
                $queue = new StringLiteral($consumerConfig['queue']);

                /** @var EventBusForwardingConsumerFactory $consumerFactory */
                $consumerFactory = $app['uitpas_event_bus_forwarding_consumer_factory'];

                return $consumerFactory->create($exchange, $queue);
            }
        );

        $app['uitpas_label_repository'] = $app->share(
            function (Application $app) {
                return new HttpUiTPASLabelsRepository(
                    new Client(),
                    $app['config']['uitpas']['labels_endpoint']
                );
            }
        );

        $app['uitpas_event_process_manager'] = $app->share(
            function (Application $app) {
                return new EventProcessManager(
                    $app['event_jsonld_repository'],
                    $app['event_command_bus'],
                    $app['uitpas_label_repository'],
                    $app['uitpas_logger']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
