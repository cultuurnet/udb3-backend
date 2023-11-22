<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\ReadModel\RDF\EventJsonToTurtleConverter;
use CultuurNet\UDB3\Event\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class EventRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RdfProjector::class,
            EventJsonToTurtleConverter::class,
        ];
    }

    public function register(): void
    {
        $graphStoreRepository = RdfServiceProvider::createGraphStoreRepository(
            $this->container->get('config')['rdf']['eventsGraphStoreUrl'],
            $this->container->get('config')['rdf']['useDeleteAndInsert'] ?? false
        );

        if ($this->container->get('config')['rdf']['useCache']) {
            $graphStoreRepository = new CacheGraphRepository($this->container->get('cache')('rdf_event'));
        }

        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $graphStoreRepository,
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['eventsRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['organizersRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['taxonomy']['terms']),
                $this->container->get('event_jsonld_repository'),
                (new EventDenormalizer())->handlesDummyOrganizers(),
                $this->container->get(AddressParser::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );

        $this->container->addShared(
            EventJsonToTurtleConverter::class,
            fn (): EventJsonToTurtleConverter => new EventJsonToTurtleConverter(
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['eventsRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['organizersRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['taxonomy']['terms']),
                $this->container->get('event_jsonld_repository'),
                (new EventDenormalizer())->handlesDummyOrganizers(),
                $this->container->get(AddressParser::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );
    }
}
