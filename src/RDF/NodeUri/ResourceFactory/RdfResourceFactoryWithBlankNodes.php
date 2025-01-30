<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri\ResourceFactory;

use EasyRdf\Resource;

final class RdfResourceFactoryWithBlankNodes implements RdfResourceFactory
{
    public function create(Resource $resource, string $nodeName, array $data): Resource
    {
        return $resource->getGraph()->newBNode([$nodeName]);
    }
}
