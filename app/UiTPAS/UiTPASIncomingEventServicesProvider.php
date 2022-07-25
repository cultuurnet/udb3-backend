<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPAS;

use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\Event\PricesUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\EventProcessManager;
use CultuurNet\UDB3\UiTPAS\Label\InMemoryUiTPASLabelsRepository;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

class UiTPASIncomingEventServicesProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['amqp.uitpas_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                // If this service gets instantiated, it's because we're running the AMQP listener for UiTPAS messages
                // so we should set the API name to UiTPAS listener.
                $app['api_name'] = ApiName::UITPAS_LISTENER;

                $uitpasDeserializerLocator = new SimpleDeserializerLocator();
                $uitpasDeserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.uitpas-events.event-card-systems-updated+json'
                    ),
                    new EventCardSystemsUpdatedDeserializer()
                );
                $uitpasDeserializerLocator->registerDeserializer(
                    new StringLiteral(
                        'application/vnd.cultuurnet.uitpas-events.event-uitpas-prices-updated+json'
                    ),
                    new PricesUpdatedDeserializer()
                );

                $consumerFactory = new EventBusForwardingConsumerFactory(
                    0,
                    $app['amqp.connection'],
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('uitpas')),
                    $uitpasDeserializerLocator,
                    $app['event_bus'],
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new UuidFactory()
                );

                $consumerConfig = $app['config']['amqp']['consumers']['uitpas'];
                $exchange = new StringLiteral($consumerConfig['exchange']);
                $queue = new StringLiteral($consumerConfig['queue']);
                return $consumerFactory->create($exchange, $queue);
            }
        );

        $app['uitpas_event_process_manager'] = $app->share(
            function (Application $app) {
                return new EventProcessManager(
                    $app['event_command_bus'],
                    InMemoryUiTPASLabelsRepository::fromStrings(
                        $app['config']['uitpas']['labels']
                    ),
                    LoggerFactory::create($app, LoggerName::forAmqpWorker('uitpas'))
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}
