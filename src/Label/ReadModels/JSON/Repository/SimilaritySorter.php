<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use ValueObjects\StringLiteral\StringLiteral;

class SimilaritySorter
{
    /**
     * @param Entity[] $entities
     * @param StringLiteral $value
     * @return bool
     */
    public function sort(array &$entities, StringLiteral $value)
    {
        return usort($entities, function ($entity1, $entity2) use ($value) {
            /** @var Entity $entity1 */
            $weight1 = $this->getWeight($entity1, $value);

            /** @var Entity $entity2 */
            $weight2 = $this->getWeight($entity2, $value);

            // The highest number matches most and needs to be first in
            // list, so it needs to be the smallest.
            if ($weight1 > $weight2) {
                return -1;
            } elseif ($weight1 < $weight2) {
                return 1;
            } else {
                return $entity1->getName()->toNative() < $entity2->getName()->toNative();
            }
        });
    }

    /**
     * @param Entity $entity
     * @param StringLiteral $value
     * @return int
     */
    private function getWeight(Entity $entity, StringLiteral $value)
    {
        // Low values are good, less to replace insert or delete.
        $weightLevenshtein = $this->calculateLevenshtein(
            $entity->getName(),
            $value
        );

        // High values are good, more similar text.
        $weightSimilarText = $this->calculateSimilarText(
            $entity->getName(),
            $value
        );

        // The higher the number the more it matches.
        return $weightSimilarText - $weightLevenshtein;
    }

    /**
     * @param StringLiteral $str1
     * @param StringLiteral $str2
     * @return int
     */
    private function calculateLevenshtein(
        StringLiteral $str1,
        StringLiteral $str2
    ) {
        return levenshtein($str1->toNative(), $str2->toNative(), 1, 1, 1);
    }

    /**
     * @param StringLiteral $str1
     * @param StringLiteral $str2
     * @return int
     */
    private function calculateSimilarText(
        StringLiteral $str1,
        StringLiteral $str2
    ) {
        $percent = 0;

        similar_text($str1->toNative(), $str2->toNative(), $percent);

        return (int)$percent;
    }
}
