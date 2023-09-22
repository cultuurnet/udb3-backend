<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use Broadway\EventHandling\SimpleEventBus;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\CachingAddressParser;
use CultuurNet\UDB3\Address\GeopuntAddressParser;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumerFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\ReadModel\RDF\RdfProjector as EventRdfProjector;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Organizer\ReadModel\RDF\RdfProjector as OrganizerRdfProjector;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector as PlaceRdfProjector;
use EasyRdf\GraphStore;
use Ramsey\Uuid\UuidFactory;

final class RdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            AddressParser::class,
            'amqp.rdf_event_bus_forwarding_consumer',
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            AddressParser::class,
            function (): AddressParser {
                $logger = LoggerFactory::create($this->getContainer(), LoggerName::forService('geopunt'));

                $parser = new GeopuntAddressParser($this->container->get('config')['geopuntAddressParser']['url'] ?? '');
                $parser->setLogger($logger);

                $parser = new CachingAddressParser($parser, $this->container->get('cache')('geopunt_addresses'));
                $parser->setLogger($logger);

                return $parser;
            }
        );

        $this->container->addShared(
            'amqp.rdf_event_bus_forwarding_consumer',
            function () {
                $eventBus = new SimpleEventBus();
                if (($this->container->get('config')['rdf']['enabled'] ?? false) === true) {
                    $eventBus->subscribe($this->container->get(PlaceRdfProjector::class));
                    $eventBus->subscribe($this->container->get(EventRdfProjector::class));
                    $eventBus->subscribe($this->container->get(OrganizerRdfProjector::class));
                }

                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerMapping = [
                    EventProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json',
                    PlaceProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.place-projected-to-jsonld+json',
                    OrganizerProjectedToJSONLD::class =>
                        'application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json',
                ];
                foreach ($deserializerMapping as $payloadClass => $contentType) {
                    $deserializerLocator->registerDeserializer(
                        $contentType,
                        new DomainMessageJSONDeserializer($payloadClass)
                    );
                }

                $consumerFactory = new EventBusForwardingConsumerFactory(
                    0,
                    $this->container->get('amqp.connection'),
                    LoggerFactory::create($this->container, LoggerName::forAmqpWorker('rdf')),
                    $deserializerLocator,
                    $eventBus,
                    $this->container->get('config')['amqp']['consumer_tag'],
                    new UuidFactory()
                );

                $consumerConfig = $this->container->get('config')['amqp']['consumers']['rdf'];
                $exchange = $consumerConfig['exchange'];
                $queue = $consumerConfig['queue'];
                return $consumerFactory->create($exchange, $queue);
            }
        );
    }

    public static function createGraphStoreRepository(string $baseUri, bool $useDeleteAndInsert): GraphStoreRepository
    {
        return new GraphStoreRepository(new GraphStore(rtrim($baseUri, '/')), $useDeleteAndInsert);
    }

    public static function createIriGenerator(string $baseUri): IriGeneratorInterface
    {
        return new CallableIriGenerator(
            fn (string $resourceId): string => rtrim($baseUri, '/') . '/' . $resourceId
        );
    }
}
