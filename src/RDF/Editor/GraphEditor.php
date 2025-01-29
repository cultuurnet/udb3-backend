<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactory;
use EasyRdf\Graph;
use EasyRdf\Literal;

final class GraphEditor
{
    private Graph $graph;
    private RdfResourceFactory $resourceFactory;


    private const TYPE_IDENTIFICATOR = 'adms:Identifier';
    private const TYPE_STRUCTURED_IDENTIFICATOR = 'generiek:GestructureerdeIdentificator';

    private const PROPERTY_IDENTIFICATOR = 'adms:identifier';

    private const PROPERTY_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_LAATST_AANGEPAST = 'dcterms:modified';

    private const PROPERTY_IDENTIFICATOR_NOTATION = 'skos:notation';

    private const PROPERTY_STRUCTURED_IDENTIFICATOR = 'generiek:gestructureerdeIdentificator';
    private const PROPERTY_NAAMRUIMTE = 'generiek:naamruimte';
    private const PROPERTY_LOKALE_IDENTIFICATOR = 'generiek:lokaleIdentificator';

    private function __construct(Graph $graph, RdfResourceFactory $resourceFactory)
    {
        $this->graph = $graph;
        $this->resourceFactory = $resourceFactory;
    }

    public static function for(Graph $graph, RdfResourceFactory $resourceFactory): self
    {
        return new self($graph, $resourceFactory);
    }

    public function setGeneralProperties(
        string $resourceIri,
        string $type,
        string $createdOn,
        string $modifiedOn
    ): self {
        $resource = $this->graph->resource($resourceIri);

        $resource->setType($type);

        $resource->set(self::PROPERTY_AANGEMAAKT_OP, new Literal($createdOn, null, 'xsd:dateTime'));
        $resource->set(self::PROPERTY_LAATST_AANGEPAST, new Literal($modifiedOn, null, 'xsd:dateTime'));

        [$namespace, $localIdentifier] = $this->splitIdentifier($resourceIri);

        $identifier = $this->resourceFactory->create($resource,self::TYPE_IDENTIFICATOR, [
            self::PROPERTY_AANGEMAAKT_OP => $createdOn,
            self::PROPERTY_LAATST_AANGEPAST => $modifiedOn,
            self::PROPERTY_NAAMRUIMTE => $namespace,
            self::PROPERTY_LOKALE_IDENTIFICATOR => $localIdentifier,
        ]);
        $identifier->add(self::PROPERTY_IDENTIFICATOR_NOTATION, new Literal($resourceIri, null, 'xsd:anyURI'));
        $resource->add(self::PROPERTY_IDENTIFICATOR, $identifier);

        $structuredIdentifier = $this->resourceFactory->create($resource,self::TYPE_STRUCTURED_IDENTIFICATOR, [
            self::PROPERTY_NAAMRUIMTE => $namespace,
            self::PROPERTY_LOKALE_IDENTIFICATOR => $localIdentifier,
        ]);
        $structuredIdentifier->add(self::PROPERTY_NAAMRUIMTE, new Literal($namespace));
        $structuredIdentifier->add(self::PROPERTY_LOKALE_IDENTIFICATOR, new Literal($localIdentifier));
        $identifier->add(self::PROPERTY_STRUCTURED_IDENTIFICATOR, $structuredIdentifier);

        return $this;
    }

    private function splitIdentifier(string $iri): array
    {
        $parts = explode('/', $iri);
        $localIdentifier = array_pop($parts);
        $namespace = implode('/', $parts) . '/';

        return [$namespace, $localIdentifier];
    }
}
