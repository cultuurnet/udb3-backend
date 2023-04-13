<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF;

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
    private const PROPERTY_IDENTIFICATOR_NAAMRUIMTE = 'generiek:naamruimte';
    private const PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR = 'generiek:lokaleIdentificator';
    private const PROPERTY_IDENTIFICATOR_VERSIE_ID = 'generiek:versieIdentificator';

    private function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public static function for(Graph $graph): self
    {
        return new self($graph);
    }

    public function setGeneralProperties(
        string $resourceUri,
        string $type,
        string $namespace,
        string $id,
        string $recordedOn
    ): self {
        $resource = $this->graph->resource($resourceUri);

        // Set the rdf:type property, but only if it is not set before to avoid needlessly shifting it to the end of the
        // list of properties in the serialized Turtle data, since set() and setType() actually do a delete() followed
        // by add().
        if ($resource->type() !== $type) {
            $resource->setType($type);
        }

        // Set the udb:workflowStatus property to draft if not set yet.
        WorkflowEditor::for($this->graph)->draft($resourceUri);

        // Set the dcterms:created property if not set yet.
        // (Otherwise it would constantly update like dcterms:modified).
        if (!$resource->hasProperty(self::PROPERTY_AANGEMAAKT_OP)) {
            $resource->set(
                self::PROPERTY_AANGEMAAKT_OP,
                new Literal($recordedOn, null, 'xsd:dateTime')
            );
        }

        // Always update the dcterms:modified property since it should change on every update to the resource.
        $resource->set(
            self::PROPERTY_LAATST_AANGEPAST,
            new Literal($recordedOn, null, 'xsd:dateTime')
        );

        // Add an adms:Indentifier if not set yet. Like rdf:type we only do this once to avoid needlessly shifting it
        // to the end of the properties in the serialized Turtle data.
        if (!$resource->hasProperty(self::PROPERTY_IDENTIFICATOR)) {
            $identificator = $this->graph->newBNode();
            $identificator->setType(self::TYPE_IDENTIFICATOR);
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NOTATION, new Literal($resourceUri, null, 'xsd:anyUri'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR, new Resource(self::PROPERTY_IDENTIFICATOR_TOEGEKEND_DOOR_AGENT));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_NAAMRUIMTE, new Literal($namespace, null, 'xsd:string'));
            $identificator->add(self::PROPERTY_IDENTIFICATOR_LOKALE_IDENTIFICATOR, new Literal($id, null, 'xsd:string'));
            $resource->add(self::PROPERTY_IDENTIFICATOR, $identificator);
        }

        // Add/update the generiek:versieIdentificator inside the linked adms:Identifier on every change.
        $identificator = $resource->getResource(self::PROPERTY_IDENTIFICATOR);
        $identificator->set(self::PROPERTY_IDENTIFICATOR_VERSIE_ID, new Literal($recordedOn, null, 'xsd:string'));

        return $this;
    }

    public function replaceLanguageValue(string $resourceUri, string $property, string $value, string $language): self
    {
        $resource = $this->graph->resource($resourceUri);

        // Get all literal values for the property, and key them by their language tag.
        // This will be an empty list if no value(s) were set before for this property.
        $literalValues = $resource->allLiterals($property);
        $languages = array_map(fn (Literal $literal): string => $literal->getLang(), $literalValues);
        $literalValuePerLanguage = array_combine($languages, $literalValues);

        // Override or add the new or updated value for the language.
        // If the language was set before, it will keep its original position in the list. If the language was not set
        // before it will be appended at the end of the list.
        $literalValuePerLanguage[$language] = new Literal($value, $language);

        // Remove all existing values of the property, then (re)add them in the intended order.
        $resource->delete($property);
        $resource->addLiteral($property, array_values($literalValuePerLanguage));

        return $this;
    }

    public function deleteLanguageValue(string $resourceUri, string $property, string $language): self
    {
        $resource = $this->graph->resource($resourceUri);

        // Get all literal values for the property, and key them by their language tag.
        // This will be an empty list if no value(s) are set for this property.
        $literalValues = $resource->allLiterals($property);
        $languages = array_map(fn (Literal $literal): string => $literal->getLang(), $literalValues);
        $literalValuePerLanguage = array_combine($languages, $literalValues);

        // Remove the value for the given language.
        unset($literalValuePerLanguage[$language]);

        // Remove all existing values of the property, then (re)add them in the intended order.
        $resource->delete($property);
        $resource->addLiteral($property, array_values($literalValuePerLanguage));

        return $this;
    }
}
