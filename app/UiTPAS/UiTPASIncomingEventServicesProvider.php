<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\UiTPAS\Event\Event\EventCardSystemsUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\Event\PricesUpdatedDeserializer;
use CultuurNet\UDB3\UiTPAS\Event\EventProcessManager;
use CultuurNet\UDB3\UiTPAS\Label\InMemoryUiTPASLabelsRepository;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Ramsey\Uuid\UuidFactory;

final class UiTPASIncomingEventServicesProvider extends AbstractServiceProvider
{
    private AMQPStreamConnection $connection;

    function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    protected function getProvidedServiceNames(): array
    {
        return [
            'amqp.uitpas_event_bus_forwarding_consumer',
            'uitpas_event_process_manager',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'amqp.uitpas_event_bus_forwarding_consumer',
            function () use ($container) {
                $uitpasDeserializerLocator = new SimpleDeserializerLocator();
                $uitpasDeserializerLocator->registerDeserializer(
                    'application/vnd.cultuurnet.uitpas-events.event-card-systems-updated+json',
                    new EventCardSystemsUpdatedDeserializer()
                );
                $uitpasDeserializerLocator->registerDeserializer(
                    'application/vnd.cultuurnet.uitpas-events.event-uitpas-prices-updated+json',
                    new PricesUpdatedDeserializer()
                );

                $consumerFactory = new EventBusForwardingConsumerFactory(
                    0,
                    $container->get('amqp.connection'),
                    LoggerFactory::create($container, LoggerName::forAmqpWorker('uitpas')),
                    $uitpasDeserializerLocator,
                    $container->get(EventBus::class),
                    $container->get('config')['amqp']['consumer_tag'],
                    new UuidFactory()
                );

                $consumerConfig = $container->get('config')['amqp']['consumers']['uitpas'];
                $exchange = $consumerConfig['exchange'];
                $queue = $consumerConfig['queue'];
                return $consumerFactory->create($exchange, $queue);
            }
        );

        $container->addShared(
            'uitpas_event_process_manager',
            function () use ($container) {
                return new EventProcessManager(
                    $container->get('event_command_bus'),
                    InMemoryUiTPASLabelsRepository::fromStrings(
                        $container->get('config')['uitpas']['labels']
                    ),
                    LoggerFactory::create($container, LoggerName::forAmqpWorker('uitpas'))
                );
            }
        );
    }
}
