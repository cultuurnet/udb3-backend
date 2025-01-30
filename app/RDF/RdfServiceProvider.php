<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Address\Parser\CachingAddressParser;
use CultuurNet\UDB3\Address\Parser\GoogleMapsAddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\RDF\NodeUri\CRC32HashGenerator;
use CultuurNet\UDB3\RDF\NodeUri\NodeUriGenerator;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactoryWithBlankNodes;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactoryWithoutBlankNodes;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;

final class RdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            AddressParser::class,
            RdfResourceFactory::class,
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            AddressParser::class,
            fn (): AddressParser => $this->createGoogleMapsAddressParser()
        );

        $this->container->addShared(
            RdfResourceFactory::class,
            function (): RdfResourceFactory {
                if ($this->getContainer()->get('config')['rdf']['blank_nodes_allowed'] ?? true) {
                    return new RdfResourceFactoryWithBlankNodes();
                }

                return new RdfResourceFactoryWithoutBlankNodes(new NodeUriGenerator(new CRC32HashGenerator()));
            }
        );
    }

    private function createGoogleMapsAddressParser(): AddressParser
    {
        $logger = LoggerFactory::create($this->getContainer(), LoggerName::forService('address_parser', 'google'));

        $parser = new GoogleMapsAddressParser(
            new StatefulGeocoder(
                new GoogleMaps(
                    new Client(),
                    null,
                    $this->container->get('config')['google_maps_api_key']
                )
            ),
        );
        $parser->setLogger($logger);

        $parser = new CachingAddressParser($parser, $this->container->get('cache')('google_addresses'));
        $parser->setLogger($logger);

        return $parser;
    }

    public static function createIriGenerator(string $baseUri): IriGeneratorInterface
    {
        return new CallableIriGenerator(
            fn (string $resourceId): string => rtrim($baseUri, '/') . '/' . $resourceId
        );
    }
}
