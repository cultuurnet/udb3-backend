<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Metadata;

use Broadway\EventDispatcher\CallableEventDispatcher;
use Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\EventSourcing\LazyCallbackMetadataEnricher;
use CultuurNet\UDB3\CommandHandling\ContextFactory;

final class MetadataServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'metadata_enricher',
            'event_stream_metadata_enricher',
            'command_bus_event_dispatcher',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'metadata_enricher',
            function () use ($container) {
                return new LazyCallbackMetadataEnricher(
                    function () use ($container) {
                        // Create a default context from application globals.
                        $context = ContextFactory::createFromGlobals($container);

                        return ContextFactory::prepareForLogging($context);
                    }
                );
            }
        );

        $container->addShared(
            'event_stream_metadata_enricher',
            function () use ($container) {
                $eventStreamDecorator = new MetadataEnrichingEventStreamDecorator();
                $eventStreamDecorator->registerEnricher(
                    $container->get('metadata_enricher')
                );
                return $eventStreamDecorator;
            }
        );

        $container->addShared(
            'command_bus_event_dispatcher',
            function () use ($container) {
                $dispatcher = new CallableEventDispatcher();
                $dispatcher->addListener(
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    function ($context) use ($container): void {
                        // Overwrite the context based on the context stored with the resque command being executed.
                        $container->addShared(
                            'context',
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
}
