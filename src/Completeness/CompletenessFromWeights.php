<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Completeness;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\ReadModel\JsonDocument;

final class CompletenessFromWeights implements Completeness
{
    public function __construct(private readonly Weights $weights, private readonly ItemType $itemType)
    {
    }

    public function calculateForDocument(JsonDocument $jsonDocument): int
    {
        $body = $jsonDocument->getAssocBody();

        $isChildrenOnly = $this->isChildrenOnlyEvent($body);

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

            if ($weight->getName() === 'typicalAgeRange' &&
                (isset($body['typicalAgeRange']) || isset($body['birthdateRange']))
            ) {
                $completeness += $weight->getValue();
                continue;
            }

            // Capacity should always be taken into account for places,
            // but for events, only if the event is childrenOnly.
            if ($weight->getName() === 'capacity' &&
                isset($body['bookingAvailability']['capacity']) &&
                (
                    $this->itemType->sameAs(ItemType::place()) ||
                    ($this->itemType->sameAs(ItemType::event()) && $this->isChildrenOnlyEvent($body))
                )
            ) {
                $completeness += $weight->getValue();
                continue;
            }

            if ($weight->getName() === 'remainingCapacity' &&
                $this->isChildrenOnlyEvent($body) &&
                isset($body['bookingAvailability'])
                && isset($body['bookingAvailability']['remainingCapacity'])
            ) {
                $completeness += $weight->getValue();
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

        return (int) ($completeness / $this->totalWeightScore($isChildrenOnly) * 100);
    }

    private function isContactPointEmpty(array $contactPoint): bool
    {
        return empty($contactPoint['phone']) && empty($contactPoint['email']) && empty($contactPoint['url']);
    }

    private function isChildrenOnlyEvent(array $body): bool
    {
        return (isset($body['childrenOnly']) && $body['childrenOnly'] === true)
            && (isset($body['calendarType']) && in_array($body['calendarType'], ['single', 'multiple']));
    }

    private function totalWeightScore(bool $isChildrenOnly): int
    {
        $totalWeightScore = 0;
        /** @var Weight $weight */
        foreach ($this->weights as $weight) {
            if ($this->itemType->sameAs(ItemType::event()) && in_array($weight->getName(), ['capacity', 'remainingCapacity'])) {
                if ($isChildrenOnly) {
                    $totalWeightScore += $weight->getValue();
                }
                continue;
            }

            $totalWeightScore += $weight->getValue();
        }

        return $totalWeightScore;
    }
}
