<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Metadata;

use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\CallableEventDispatcher;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\ApiGuard\Consumer\Consumer;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\EventSourcing\LazyCallbackMetadataEnricher;
use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\CommandHandling\ContextFactory;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class MetadataServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['context'] = null;

        $app['metadata_enricher'] = $app::share(
            function (Application $app) {
                return new LazyCallbackMetadataEnricher(
                    function () use ($app) {
                        // Create a default context from application globals.
                        $context = ContextFactory::createContext(
                            $app[CurrentUser::class]->getId(),
                            $app[JsonWebToken::class],
                            $app[ApiKey::class],
                            $app['api_name'],
                            $app[Consumer::class]
                        );

                        // Allow some processes to overwrite the context, like resque workers.
                        if ($app['context'] instanceof Metadata) {
                            $context = $app['context'];
                        }

                        return ContextFactory::prepareForLogging($context);
                    }
                );
            }
        );

        $app['event_stream_metadata_enricher'] = $app::share(
            function ($app) {
                $eventStreamDecorator = new MetadataEnrichingEventStreamDecorator();
                $eventStreamDecorator->registerEnricher(
                    $app['metadata_enricher']
                );
                return $eventStreamDecorator;
            }
        );

        $app['command_bus_event_dispatcher'] = $app::share(
            function (Application $app) {
                $dispatcher = new CallableEventDispatcher();
                $dispatcher->addListener(
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    function ($context) use ($app) {
                        // Overwrite the context based on the context stored with the resque command being executed.
                        $app['context'] = $app::share(
                            function () use ($context) {
                                return $context;
                            }
                        );
                    }
                );

                return $dispatcher;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
