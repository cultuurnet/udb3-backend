<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

final class NodeUriGenerator
{
    private HashGenerator $hashGenerator;

    public function __construct(HashGenerator $hashGenerator)
    {
        $this->hashGenerator = $hashGenerator;
    }

    public function generate(string $nodeName, array $fields): string
    {
        // Convert "schema:location" to "location".
        if (str_contains($nodeName, ':')) {
            $nodeName = substr($nodeName, strrpos($nodeName, ':') + 1);
        }

        // Convert AddressDetail to addressDetail
        $nodeName = lcfirst($nodeName);

        return sprintf('#%s-%s', $nodeName, $this->hashGenerator->generate($fields));
    }
}
