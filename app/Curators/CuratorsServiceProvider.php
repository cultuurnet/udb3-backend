<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Curators;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Curators\DBALNewsArticleRepository;
use CultuurNet\UDB3\Curators\NewsArticleRepository;
use CultuurNet\UDB3\Deserializer\SimpleDeserializerLocator;
use CultuurNet\UDB3\Broadway\AMQP\EventBusForwardingConsumer;
use CultuurNet\UDB3\Curators\Events\NewsArticleAboutEventAddedJSONDeserializer;
use CultuurNet\UDB3\Curators\LabelFactory;
use CultuurNet\UDB3\Curators\NewsArticleProcessManager;
use CultuurNet\UDB3\Http\Curators\CreateNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\DeleteNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticleRequestHandler;
use CultuurNet\UDB3\Http\Curators\GetNewsArticlesRequestHandler;
use CultuurNet\UDB3\Http\Curators\UpdateNewsArticleRequestHandler;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

final class CuratorsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['curators_event_bus_forwarding_consumer'] = $app->share(
            function (HybridContainerApplication $app) {
                $deserializerLocator = new SimpleDeserializerLocator();
                $deserializerLocator->registerDeserializer(
                    NewsArticleAboutEventAddedJSONDeserializer::getContentType(),
                    new NewsArticleAboutEventAddedJSONDeserializer()
                );

                // If this service gets instantiated, it's because we're running the AMQP listener for Curators messages
                // so we should set the API name to Curators listener.
                $app['api_name'] = ApiName::CURATORS_LISTENER;

                $consumer = new EventBusForwardingConsumer(
                    $app['amqp.connection'],
                    $app[EventBus::class],
                    $deserializerLocator,
                    new StringLiteral($app['config']['amqp']['consumer_tag']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['exchange']),
                    new StringLiteral($app['config']['amqp']['consumers']['curators']['queue']),
                    new UuidFactory(),
                );

                $consumer->setLogger(LoggerFactory::create($app->getLeagueContainer(), LoggerName::forAmqpWorker('curators')));

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

        $app[NewsArticleRepository::class] = $app->share(
            fn (Application $app) => new DBALNewsArticleRepository($app['dbal_connection'])
        );

        $app[GetNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new GetNewsArticleRequestHandler(
                $app[NewsArticleRepository::class]
            )
        );

        $app[GetNewsArticlesRequestHandler::class] = $app->share(
            fn (Application $application) => new GetNewsArticlesRequestHandler(
                $app[NewsArticleRepository::class]
            )
        );

        $app[CreateNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new CreateNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
                $app['uuid_generator'],
            )
        );

        $app[UpdateNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
            )
        );

        $app[DeleteNewsArticleRequestHandler::class] = $app->share(
            fn (Application $application) => new DeleteNewsArticleRequestHandler(
                $app[NewsArticleRepository::class],
            )
        );
    }

    public function boot(Application $app)
    {
    }
}
