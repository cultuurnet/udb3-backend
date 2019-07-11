<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use CultuurNet\BroadwayAMQP\CommandBusForwardingConsumer;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Silex\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class CuratorsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['curators_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../../log/curators-events.log');
            }
        );

        $app['curators_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('curators-events');
                $logger->pushHandler($app['curators_log_handler']);
                return $logger;
            }
        );

        $app['curators_deserializer_locator'] = $app->share(
            function () {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    NewsArticleAboutEventAddedJSONDeserializer::getContentType(),
                    new NewsArticleAboutEventAddedJSONDeserializer()
                );
                return $deserializerLocator;
            }
        );

        $app['curators_command_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                $consumer = new CommandBusForwardingConsumer(
                    $app['amqp.connection'],
                    $app['event_command_bus'],
                    $app['curators_deserializer_locator'],
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['exchange']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['queue'])
                );

                $consumer->setLogger($app['curators_logger']);

                return $consumer;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
