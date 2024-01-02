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
            function (): AddressParser {
                $logger = LoggerFactory::create($this->getContainer(), LoggerName::forService('geopunt'));

                $parser = new GeopuntAddressParser($this->container->get('config')['geopuntAddressParser']['url'] ?? '');
                $parser->setLogger($logger);

                $parser = new CachingAddressParser($parser, $this->container->get('cache')('geopunt_addresses'));
                $parser->setLogger($logger);

                return $parser;
            }
        );
    }

    public static function createIriGenerator(string $baseUri): IriGeneratorInterface
    {
        return new CallableIriGenerator(
            fn (string $resourceId): string => rtrim($baseUri, '/') . '/' . $resourceId
        );
    }
}
