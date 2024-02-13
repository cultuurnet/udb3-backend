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

    public function calculateForDocument(JsonDocument $jsonDocument): int
    {
        $body = $jsonDocument->getAssocBody();

        $completeness = 0;
        /** @var Weight $weight */
        foreach ($this->weights as $weight) {
            if ($weight->getName() === 'type' && isset($body['terms'])) {
                foreach ($body['terms'] as $term) {
                    if ($term['domain'] === 'eventtype') {
                        $completeness += $weight->getValue();
                    }
                }
                continue;
            }

            if ($weight->getName() === 'theme' && isset($body['terms'])) {
                foreach ($body['terms'] as $term) {
                    if ($term['domain'] === 'theme') {
                        $completeness += $weight->getValue();
                    }
                }
                continue;
            }

            if (!isset($body[$weight->getName()])) {
                continue;
            }

            if ($weight->getName() === 'contactPoint' && $this->isContactPointEmpty($body['contactPoint'])) {
                continue;
            }

            if ($weight->getName() === 'description' && isset($body['description'])) {
                $language = $body['mainLanguage'] ?? array_key_first($body['description']);
                if (isset($body['description'][$language]) && strlen($body['description'][$language]) > 200) {
                    $completeness += $weight->getValue();
                }

                continue;
            }

            $completeness += $weight->getValue();
        }

        return $completeness;
    }

    private function isContactPointEmpty(array $contactPoint): bool
    {
        return empty($contactPoint['phone']) && empty($contactPoint['email']) && empty($contactPoint['url']);
    }
}
