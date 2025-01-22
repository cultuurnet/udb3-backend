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
        return sprintf('#%s-%s', $nodeName, $this->hashGenerator->generate($fields));
    }
}
