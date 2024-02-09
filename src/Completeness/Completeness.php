<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\ReadModel\JsonDocument;

interface Completeness
{
    public function calculateForDocument(JsonDocument $jsonDocument): int;

    public function getWeight(string $name): Weight;
}
