<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Metadata;

use Broadway\Domain\Metadata;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher;
use CultuurNet\UDB3\Silex\CommandHandling\ContextFactory;
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
                        $this->setEventStreamMetadata($app['execution_context_metadata_enricher'], $context);
                    }
                );

                return $dispatcher;
            }
        );

        $app->before(
            function (Request $request, Application $app) {
                $context = ContextFactory::createContext(
                    $app['current_user'],
                    $app['jwt'],
                    $app['api_key'],
                    $app['culturefeed_token_credentials'],
                    $request
                );

                $this->setEventStreamMetadata($app['execution_context_metadata_enricher'], $context);
            },
            Application::LATE_EVENT
        );
    }

    public function boot(Application $app)
    {
    }

    private function setEventStreamMetadata(
        ExecutionContextMetadataEnricher $executionContextMetadataEnricher,
        ?Metadata $metadata
    ): void {
        $executionContextMetadataEnricher->setContext(
            $metadata ? ContextFactory::prepareForLogging($metadata) : null
        );
    }
}
