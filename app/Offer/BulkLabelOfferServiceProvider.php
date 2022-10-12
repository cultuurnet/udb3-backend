<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Offer\AddLabelToMultipleRequestHandler;
use CultuurNet\UDB3\Http\Offer\AddLabelToQueryRequestHandler;
use CultuurNet\UDB3\Offer\Commands\AddLabelToMultipleJSONDeserializer;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Search\Sapi3SearchServiceProvider;

final class BulkLabelOfferServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'bulk_label_offer_command_handler',
            AddLabelToQueryRequestHandler::class,
            AddLabelToMultipleRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'bulk_label_offer_command_handler',
            function () use ($container) {
                $searchResultsGenerator = new ResultsGenerator(
                    $container->get(Sapi3SearchServiceProvider::SEARCH_SERVICE_OFFERS)
                );
                $searchResultsGenerator->setLogger(
                    LoggerFactory::create($container, LoggerName::forResqueWorker('bulk-label-offer', 'search'))
                );

                return new BulkLabelCommandHandler(
                    $searchResultsGenerator,
                    $container->get('event_command_bus')
                );
            }
        );

        $container->addShared(
            AddLabelToQueryRequestHandler::class,
            fn () => new AddLabelToQueryRequestHandler($container->get('bulk_label_offer_command_bus'))
        );

        $container->addShared(
            AddLabelToMultipleRequestHandler::class,
            fn () => new AddLabelToMultipleRequestHandler(
                new AddLabelToMultipleJSONDeserializer(
                    new IriOfferIdentifierJSONDeserializer($container->get('iri_offer_identifier_factory'))
                ),
                $container->get('bulk_label_offer_command_bus')
            )
        );
    }
}
