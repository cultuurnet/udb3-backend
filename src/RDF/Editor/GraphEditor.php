<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use EasyRdf\Graph;
use EasyRdf\Literal;

final class GraphEditor
{
    private Graph $graph;

    private const TYPE_IDENTIFICATOR = 'adms:Identifier';
    private const TYPE_STRUCTURED_IDENTIFICATOR = 'generiek:GestructureerdeIdentificator';

    private const PROPERTY_IDENTIFICATOR = 'adms:identifier';

    private const PROPERTY_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_LAATST_AANGEPAST = 'dcterms:modified';

    private const PROPERTY_IDENTIFICATOR_NOTATION = 'skos:notation';

    private const PROPERTY_STRUCTURED_IDENTIFICATOR = 'generiek:gestructureerdeIdentificator';
    private const PROPERTY_NAAMRUIMTE = 'generiek:naamruimte';
    private const PROPERTY_LOKALE_IDENTIFICATOR = 'generiek:lokaleIdentificator';

    private function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public static function for(Graph $graph): self
    {
        return new self($graph);
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

        $identifier = $this->graph->newBNode();
        $identifier->setType(self::TYPE_IDENTIFICATOR);
        $identifier->add(self::PROPERTY_IDENTIFICATOR_NOTATION, new Literal($resourceIri, null, 'xsd:anyUri'));
        $resource->add(self::PROPERTY_IDENTIFICATOR, $identifier);

        list($namespace, $localIdentifier) = $this->splitIdentifier($resourceIri);
        $structuredIdentifier = $this->graph->newBNode();
        $structuredIdentifier->setType(self::TYPE_STRUCTURED_IDENTIFICATOR);
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
