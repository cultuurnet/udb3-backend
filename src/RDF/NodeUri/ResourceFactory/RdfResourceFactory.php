<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri\ResourceFactory;

use CultuurNet\UDB3\RDF\NodeUri\NodeUriGenerator;
use EasyRdf\Resource;

final class RdfResourceFactory
{
    private NodeUriGenerator $nodeUriGenerator;

    public function __construct(NodeUriGenerator $nodeUriGenerator)
    {
        $this->nodeUriGenerator = $nodeUriGenerator;
    }

    public function create(Resource $resource, string $nodeName, array $data): Resource
    {
        return $resource->getGraph()->resource(
            $this->nodeUriGenerator->generate(
                $nodeName,
                $data,
            ),
            [$nodeName]
        );
    }
}
