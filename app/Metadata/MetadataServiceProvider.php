<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Metadata;

use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher;
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
        $app['execution_context_metadata_enricher'] = $app::share(
            function () {
                return new ExecutionContextMetadataEnricher();
            }
        );

        $app['event_stream_metadata_enricher'] = $app::share(
            function ($app) {
                $eventStreamDecorator = new MetadataEnrichingEventStreamDecorator();
                $eventStreamDecorator->registerEnricher(
                    $app['execution_context_metadata_enricher']
                );
                return $eventStreamDecorator;
            }
        );

        $app['command_bus_event_dispatcher'] = $app::share(
            function ($app) {
                $dispatcher = new EventDispatcher();
                $dispatcher->addListener(
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    function ($context) use ($app) {
                        self::setEventStreamMetadata($app, $context);
                    }
                );

                return $dispatcher;
            }
        );

        $app->before(
            function (Request $request, Application $app) {
                // If we're handling requests, the API used is usually the JSON-LD API.
                $apiName = ApiName::JSONLD;

                // Except if we're handling requests under the /imports/ path, then we're dealing with the JSON-LD imports API.
                if (strpos($request->getRequestUri(), ImportControllerProvider::PATH) === 0) {
                    $apiName = ApiName::JSONLD_IMPORTS;
                }

                $context = ContextFactory::createContext(
                    $app['current_user'],
                    $app['jwt'],
                    $app['api_key'],
                    $apiName,
                    $app['culturefeed_token_credentials'],
                    $request
                );

                self::setEventStreamMetadata($app, $context);
            },
            Application::LATE_EVENT
        );
    }

    public function boot(Application $app)
    {
    }

    public static function setEventStreamMetadata(
        Application $app,
        ?Metadata $metadata
    ): void {
        $app['execution_context_metadata_enricher']->setContext(
            $metadata ? ContextFactory::prepareForLogging($metadata) : null
        );
    }
}
