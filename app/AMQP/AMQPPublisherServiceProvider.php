<?php

namespace CultuurNet\SilexAMQP;

use CultuurNet\BroadwayAMQP\AMQPPublisher;
use CultuurNet\BroadwayAMQP\DomainMessage\AnyOf;
use CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf;
use CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection;
use CultuurNet\BroadwayAMQP\Message\Body\EntireDomainMessageBodyFactory;
use CultuurNet\BroadwayAMQP\Message\DelegatingAMQPMessageFactory;
use CultuurNet\BroadwayAMQP\Message\Properties\CompositePropertiesFactory;
use CultuurNet\BroadwayAMQP\Message\Properties\ContentTypeLookup;
use CultuurNet\BroadwayAMQP\Message\Properties\ContentTypePropertiesFactory;
use CultuurNet\BroadwayAMQP\Message\Properties\CorrelationIdPropertiesFactory;
use CultuurNet\BroadwayAMQP\Message\Properties\DeliveryModePropertiesFactory;
use PhpAmqpLib\Message\AMQPMessage;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AMQPPublisherServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['amqp.publisher.specification'] = $app->share(
            function (Application $app) {
                $classes = new SpecificationCollection();

                foreach (array_keys($app['amqp.publisher.content_type_map']) as $className) {
                    $classes = $classes->with(
                        new PayloadIsInstanceOf($className)
                    );
                }

                return new AnyOf($classes);
            }
        );

        $app['amqp.publisher.body_factory'] = $app->share(
            function (Application $app) {
                return new EntireDomainMessageBodyFactory();
            }
        );

        $app['amqp.publisher.properties_factory'] = $app->share(
            function (Application $app) {
                return (new CompositePropertiesFactory())
                    ->with(new CorrelationIdPropertiesFactory())
                    ->with(new DeliveryModePropertiesFactory(AMQPMessage::DELIVERY_MODE_PERSISTENT))
                    ->with(
                        new ContentTypePropertiesFactory(
                            new ContentTypeLookup($app['amqp.publisher.content_type_map'])
                        )
                    );
            }
        );

        $app['amqp.publisher.message_factory'] = $app->share(
            function (Application $app) {
                return new DelegatingAMQPMessageFactory(
                    $app['amqp.publisher.body_factory'],
                    $app['amqp.publisher.properties_factory']
                );
            }
        );

        $app['amqp.publisher'] = $app->share(
            function (Application $app) {
                $connection = $app['amqp.connection'];
                $channel = $connection->channel();

                return new AMQPPublisher(
                    $channel,
                    $app['amqp.publisher.exchange_name'],
                    $app['amqp.publisher.specification'],
                    $app['amqp.publisher.message_factory']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
