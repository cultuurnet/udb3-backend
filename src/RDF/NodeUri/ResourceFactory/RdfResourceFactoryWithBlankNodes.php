<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri\ResourceFactory;

use EasyRdf\Resource;

/*
 * @todo The current implementation, can be removed after https://jira.publiq.be/browse/III-6450 has been deployed and verified on production.
 * */

final class ResourceFactoryWithBlankNodes implements ResourceFactory
{
    public function create(Resource $resource, string $nodeName, array $data): Resource
    {
        return $resource->getGraph()->newBNode([$nodeName]);
    }
}
