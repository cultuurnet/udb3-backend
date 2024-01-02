<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Address\Parser\CachingAddressParser;
use CultuurNet\UDB3\Address\Parser\GeopuntAddressParser;
use CultuurNet\UDB3\Address\Parser\GoogleMapsAddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;

final class RdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            AddressParser::class,
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            AddressParser::class,
            fn (): AddressParser => $this->createAddressParser(
                $this->container->get('config')['google_maps_api_key'] ?? false
            )
        );
    }

    private function createAddressParser(bool $googleMapsAddressParser): AddressParser
    {
        if ($googleMapsAddressParser) {
            return $this->createGoogleMapsAddressParser();
        }

        return $this->createGeoPuntAddressParser();
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

    private function createGeoPuntAddressParser(): AddressParser
    {
        $logger = LoggerFactory::create($this->getContainer(), LoggerName::forService('address_parser', 'geopunt'));

        $parser = new GeopuntAddressParser($this->container->get('config')['geopuntAddressParser']['url'] ?? '');
        $parser->setLogger($logger);

        $parser = new CachingAddressParser($parser, $this->container->get('cache')('geopunt_addresses'));
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
