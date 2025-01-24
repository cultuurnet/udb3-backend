<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Event\ReadModel\RDF\EventJsonToTurtleConverter;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\ResourceFactory;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use CultuurNet\UDB3\RDF\RdfServiceProvider;

final class EventRdfServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            EventJsonToTurtleConverter::class,
        ];
    }

    public function register(): void
    {
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
                $this->container->get(ResourceFactory::class),
                $this->container->get(ImageNormalizer::class),
                LoggerFactory::create($this->getContainer(), LoggerName::forService('rdf'))
            )
        );
    }
}
