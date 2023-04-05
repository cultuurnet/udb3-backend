<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\GeopuntAddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Place\ReadModel\RDF\RdfProjector;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class PlaceRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            RdfProjector::class,
            AddressParser::class,
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            RdfProjector::class,
            fn (): RdfProjector => new RdfProjector(
                $this->container->get(MainLanguageRepository::class),
                RdfServiceProvider::createGraphStoreRepository($this->container->get('config')['rdf']['placesGraphStoreUrl']),
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['placesRdfBaseUri']),
                $this->container->get(AddressParser::class)
            )
        );

        $this->container->addShared(
            AddressParser::class,
            function (): AddressParser {
                $logger = LoggerFactory::create($this->getContainer(), LoggerName::forService('geopunt'));

                $parser = new GeopuntAddressParser();
                $parser->setLogger($logger);
                return $parser;
            }
        );
    }
}
