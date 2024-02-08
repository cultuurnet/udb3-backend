<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\ReadModel\JsonDocument;

final class CompletenessFromWeights implements Completeness
{
    private Weights $weights;

    public function __construct(Weights $weights)
    {
        $this->weights = $weights;
    }

    public function forDocument(JsonDocument $jsonDocument): int
    {
        $body = $jsonDocument->getAssocBody();

        $completeness = 0;
        foreach ($this->weights as $weight) {
            if (!isset($body[$weight->getName()])) {
                continue;
            }
            $completeness += $weight->getValue();
        }

        return $completeness;
    }

    public function getWeight(string $name): Weight
    {
        return $this->weights->filter(
            fn (Weight $weight) => $weight->getName() === $name
        )->getFirst();
    }
}
