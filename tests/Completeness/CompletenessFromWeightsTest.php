<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class CompletenessFromWeightsTest extends TestCase
{
    public function testCalculateForDocumentContactPointEmptyArray(): void
    {
        $completeFromWeights = new CompletenessFromWeights(
            Weights::fromConfig([
                'name' => 12,
                'contactPoint' => 3,
            ])
        );

        $jsonDocument = new JsonDocument(
            'e3c976cf-d9e3-4995-942f-05f0a5900716',
            '{
                "name": {
                    "nl": "Permanent event"
                },
                "contactPoint": {}
            }'
        );

        $score = $completeFromWeights->calculateForDocument($jsonDocument);
        $this->assertEquals(12, $score);
    }

    public function testCalculateForDocumentContactPointMissing(): void
    {
        $completeFromWeights = new CompletenessFromWeights(
            Weights::fromConfig([
                'name' => 12,
                'contactPoint' => 3,
            ])
        );

        $jsonDocument = new JsonDocument(
            'e3c976cf-d9e3-4995-942f-05f0a5900716',
            '{
                "name": {
                    "nl": "Permanent event"
                }
            }'
        );

        $score = $completeFromWeights->calculateForDocument($jsonDocument);
        $this->assertEquals(12, $score);
    }
}
