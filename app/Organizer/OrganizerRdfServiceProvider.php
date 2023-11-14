<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Organizer\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\CacheGraphRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class OrganizerRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RdfProjector::class,
        ];
    }

    public function register(): void
    {
        $graphStoreRepository = RdfServiceProvider::createGraphStoreRepository(
            $this->container->get('config')['rdf']['organizersGraphStoreUrl'],
            $this->container->get('config')['rdf']['useDeleteAndInsert'] ?? false
        );

        if ($this->container->get('config')['rdf']['useCache']) {
            $graphStoreRepository = new CacheGraphRepository($this->container->get('cache')('rdf_organizer'));
        }

        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $graphStoreRepository,
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['organizersRdfBaseUri']),
                $this->container->get('organizer_jsonld_repository'),
                new OrganizerDenormalizer(),
                $this->container->get(AddressParser::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );
    }
}
