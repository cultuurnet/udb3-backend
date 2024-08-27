<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\AMQP;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\Broadway\AMQP\AMQPPublisher;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\AnyOf;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\PayloadIsInstanceOf;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationCollection;
use CultuurNet\UDB3\Broadway\AMQP\Message\Body\EntireDomainMessageBodyFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\DelegatingAMQPMessageFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\CompositePropertiesFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\ContentTypeLookup;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\ContentTypePropertiesFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\CorrelationIdPropertiesFactory;
use CultuurNet\UDB3\Broadway\AMQP\Message\Properties\DeliveryModePropertiesFactory;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Offer\ProcessManagers\RelatedDocumentProjectedToJSONLDDispatcher;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class AMQPPublisherServiceProvider extends AbstractServiceProvider
{
    private AMQPStreamConnection $connection;
    private ?JsonWebToken $jsonWebToken;

    public function __construct()
    {
        $container = $this->getContainer();
        $this->connection = $container->get(AMQPStreamConnection::class);
        $this->jsonWebToken = $container->get(JsonWebToken::class);
    }


    public function getProvidedServiceNames(): array
    {
        return [AMQPPublisher::class];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            AMQPPublisher::class,
            function () use ($container): ReplayFilteringEventListener {
                $channel = $this->connection->channel();

                $contentTypeMapping = [
                    EventProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json',
                    PlaceProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.place-projected-to-jsonld+json',
                    OrganizerProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json',
                ];

                $specificationCollection = new SpecificationCollection();
                foreach (array_keys($contentTypeMapping) as $className) {
                    $specificationCollection = $specificationCollection->with(
                        new PayloadIsInstanceOf($className)
                    );
                }
                $anyOfSpecification = new AnyOf($specificationCollection);

                $messageFactory = new DelegatingAMQPMessageFactory(
                    new EntireDomainMessageBodyFactory(),
                    (new CompositePropertiesFactory())
                        ->with(new CorrelationIdPropertiesFactory())
                        ->with(new DeliveryModePropertiesFactory(AMQPMessage::DELIVERY_MODE_PERSISTENT))
                        ->with(
                            new ContentTypePropertiesFactory(
                                new ContentTypeLookup($contentTypeMapping)
                            )
                        )
                );

                $publisher = new AMQPPublisher(
                    $channel,
                    $container->get('config')['amqp']['publish']['udb3']['exchange'],
                    $anyOfSpecification,
                    $messageFactory,
                    function (DomainMessage $domainMessage) use ($container) {
                        // Route ProjectedToJSONLD messages that are triggered by
                        // RelatedDocumentProjectedToJSONLDDispatcher to the "related" queue.
                        if (RelatedDocumentProjectedToJSONLDDispatcher::hasDispatchedMessage($domainMessage) === true) {
                            return 'related';
                        }

                        // Check if the API key or Client ID is in the list of keys / ids that should have their
                        // messages routed to the "cli" queue to offload the API queue if the API key or Client ID is
                        // sending A LOT of requests. (Configured manually in config.yml)
                        $jwt = $this->jsonWebToken;
                        $clientId = $jwt instanceof JsonWebToken ? $jwt->getClientId() : null;
                        $apiKey = $container->get(ApiKey::class);
                        $apiKey = $apiKey instanceof ApiKey ? $apiKey->toString() : null;
                        if (in_array($clientId, $container->get('config')['amqp']['publish']['udb3']['cli']['client_ids'], true) ||
                            in_array($apiKey, $container->get('config')['amqp']['publish']['udb3']['cli']['api_keys'], true)) {
                            return 'cli';
                        }

                        // Check if the app is running in the CLI environment and route the messages to the "cli" queue.
                        // If not, route them to the "api" queue.
                        return $container->get(ApiName::class) === ApiName::CLI ? 'cli' : 'api';
                    }
                );

                return new ReplayFilteringEventListener($publisher);
            }
        );
    }
}
