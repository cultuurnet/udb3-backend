<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\RDF\NodeUri;

interface HashGenerator
{
    public function generate(array $data): string;
}
