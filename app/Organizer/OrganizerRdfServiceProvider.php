<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use CultuurNet\UDB3\Organizer\ReadModel\RDF\OrganizerJsonToTurtleConverter;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class OrganizerRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            OrganizerJsonToTurtleConverter::class,
        ];
    }

    public function register(): void
    {
        $this->container->addShared(
            OrganizerJsonToTurtleConverter::class,
            fn (): OrganizerJsonToTurtleConverter => new OrganizerJsonToTurtleConverter(
                RdfServiceProvider::createIriGenerator($this->container->get('config')['rdf']['organizersRdfBaseUri']),
                $this->container->get('organizer_jsonld_repository'),
                new OrganizerDenormalizer(),
                $this->container->get(AddressParser::class),
                $this->container->get(ImageNormalizer::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );
    }
}
