<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\Editor;

use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;

final class WorkflowEditor
{
    private Graph $graph;

    private const PROPERTY_WORKFLOW_STATUS = 'udb:workflowStatus';
    private const PROPERTY_WORKFLOW_STATUS_DRAFT = 'https://data.publiq.be/concepts/workflowStatus/draft';
    private const PROPERTY_WORKFLOW_STATUS_READY_FOR_VALIDATION = 'https://data.publiq.be/concepts/workflowStatus/ready-for-validation';
    private const PROPERTY_AVAILABLE_FROM = 'udb:availableFrom';

    private function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public static function for(Graph $graph): self
    {
        return new self($graph);
    }

    public function draft(string $resourceUri): void
    {
        $resource = $this->graph->resource($resourceUri);

        if (!$resource->hasProperty(self::PROPERTY_WORKFLOW_STATUS)) {
            $resource->set(
                self::PROPERTY_WORKFLOW_STATUS,
                new Resource(self::PROPERTY_WORKFLOW_STATUS_DRAFT)
            );
        }
    }

    public function publish(string $resourceUri, string $publicationDate): void
    {
        $resource = $this->graph->resource($resourceUri);

        $resource->set(
            self::PROPERTY_WORKFLOW_STATUS,
            new Resource(self::PROPERTY_WORKFLOW_STATUS_READY_FOR_VALIDATION)
        );

        $resource->set(
            self::PROPERTY_AVAILABLE_FROM,
            new Literal($publicationDate, null, 'xsd:dateTime')
        );
    }
}
