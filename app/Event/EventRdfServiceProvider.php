<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\RDF\CacheLocationIdRepository;
use CultuurNet\UDB3\Event\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class EventRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RdfProjector::class,
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $this->container->get(MainLanguageRepository::class),
                RdfServiceProvider::createGraphStoreRepository($this->container->get('config')['rdf']['eventsGraphStoreUrl']),
                new CacheLocationIdRepository($this->container->get('cache')('rdf_location_id')),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['eventsRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['taxonomy']['terms']),
            )
        );
    }
}
