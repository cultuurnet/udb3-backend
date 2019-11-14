<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Metadata;

use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\EventSourcing\LazyCallbackMetadataEnricher;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\CommandHandling\ContextFactory;
use CultuurNet\UDB3\Silex\Import\ImportControllerProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

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
                        $context = ContextFactory::createFromGlobals($app);

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
                $dispatcher = new EventDispatcher();
                $dispatcher->addListener(
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    function ($context) use ($app) {
                        // Overwrite the context based on the context stored with the resque command being executed.
                        $app['context'] = $app::share(
                            function () use ($context) {
                                return $context;
                            });
                    }
                );

                return $dispatcher;
            }
        );

        $app->before(
            function (Request $request, Application $app) {
                // If we're handling requests, the API used is usually the JSON-LD API.
                $app['api_name'] = ApiName::JSONLD;

                // Except if we're handling requests under the /imports/ path, then we're dealing with the JSON-LD imports API.
                if (strpos($request->getRequestUri(), ImportControllerProvider::PATH) === 0) {
                    $app['api_name'] = ApiName::JSONLD_IMPORTS;
                }
            },
            Application::LATE_EVENT
        );
    }

    public function boot(Application $app)
    {
    }
}
