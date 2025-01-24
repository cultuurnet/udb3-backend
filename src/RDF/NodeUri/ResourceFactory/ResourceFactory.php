<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri\ResourceFactory;

use EasyRdf\Resource;

interface RdfResourceFactory
{
    public function create(Resource $resource, string $nodeName, array $data): Resource;
}
