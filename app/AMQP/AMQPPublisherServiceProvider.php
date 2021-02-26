<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\AMQP;

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
