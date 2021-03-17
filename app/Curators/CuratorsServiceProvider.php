<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumer;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
use CultuurNet\UDB3\Curators\LabelFactory;
use CultuurNet\UDB3\Curators\NewsArticleProcessManager;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class CuratorsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
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

        $app['curators_event_bus_forwarding_consumer'] = $app->share(
            function (Application $app) {
                // If this service gets instantiated, it's because we're running the AMQP listener for Curators messages
                // so we should set the API name to Curators listener.
                $app['api_name'] = ApiName::CURATORS_LISTENER;

                $consumer = new EventBusForwardingConsumer(
                    $app['amqp.connection'],
                    $app['event_bus'],
                    $app['curators_deserializer_locator'],
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['exchange']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['queue'])
                );

                $consumer->setLogger(LoggerFactory::create($app, new LoggerName('curators-events')));

                return $consumer;
            }
        );

        $app['curators_news_article_process_manager'] = $app->share(
            function (Application $app) {
                return new NewsArticleProcessManager(
                    new LabelFactory(
                        $app['config']['curator_labels']
                    ),
                    $app['event_command_bus']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
