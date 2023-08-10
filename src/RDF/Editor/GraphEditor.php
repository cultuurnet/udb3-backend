<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class GraphEditor
{
    private Graph $graph;

    private const TYPE_IDENTIFICATOR = 'adms:Identifier';

    private const PROPERTY_IDENTIFICATOR = 'adms:identifier';

    private const PROPERTY_AANGEMAAKT_OP = 'dcterms:created';
    private const PROPERTY_LAATST_AANGEPAST = 'dcterms:modified';

    private const PROPERTY_IDENTIFICATOR_NOTATION = 'skos:notation';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR = 'dcterms:creator';
    private const PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT = 'https://fixme.com/example/dataprovider/publiq';

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

        $resource->set(
            self::PROPERTY_AANGEMAAKT_OP,
            new Literal($createdOn, null, 'xsd:dateTime')
        );

        $resource->set(
            self::PROPERTY_LAATST_AANGEPAST,
            new Literal($modifiedOn, null, 'xsd:dateTime')
        );

        if (!$resource->hasProperty(self::PROPERTY_IDENTIFICATOR)) {
            $identificator = $this->graph->newBNode();
            $identificator->setType(self::TYPE_IDENTIFICATOR);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NOTATION, new Literal($resourceIri, null, 'xsd:anyUri'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR, new Resource(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT));
            $resource->add(self::PROPERTY_IDENTIFICATOR, $identificator);
        }

        return $this;
    }
}
