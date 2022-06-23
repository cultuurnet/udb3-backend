<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultureFeed_Cdb_Data_Keyword;

final class LabelsArray
{
    // Multidimensional associative array of labels:
    //  - The key is the lowercase label name
    //  - The value is an associative array with label name and visibility
    //
    //  Example:
    //      [
    //          'visible-label' => ['labelName' => 'Visible-Label', 'isVisible => true],
    //          'invisible-label' => ['labelName' => 'Invisible-Label', 'isVisible => false],
    //      ]
    private array $labels;

    public function __construct()
    {
        $this->labels = [];
    }

    /**
     * @param CultureFeed_Cdb_Data_Keyword[] $keywords
     */
    public static function createFromKeywords(array $keywords): LabelsArray
    {
        $primitiveLabels = new LabelsArray();

        foreach ($keywords as $keyword) {
            $primitiveLabels->addLabel($keyword->getValue(), $keyword->isVisible());
        }

        return $primitiveLabels;
    }

    public function addLabel(string $labelName, bool $isVisible): void
    {
        if (!$this->containsLabel($labelName)) {
            $this->labels[$this->labelNameToLowerCase($labelName)] = [
                'labelName' => $labelName,
                'isVisible' => $isVisible,
            ];
        }
    }

    public function removeLabel(string $labelName): void
    {
        unset($this->labels[$this->labelNameToLowerCase($labelName)]);
    }

    public function getLabel(string $labelName): ?array
    {
        if (!$this->containsLabel($labelName)) {
            return null;
        }

        return $this->labels[$this->labelNameToLowerCase($labelName)];
    }

    public function containsLabel(string $labelName): bool
    {
        return array_key_exists($this->labelNameToLowerCase($labelName), $this->labels);
    }

    public function toArray(): array
    {
        return $this->labels;
    }

    public function toArrayOfStringNames(): array
    {
        return array_map(
            fn ($label) => $label['labelName'],
            $this->toArray()
        );
    }

    private function labelNameToLowerCase(string $labelName): string
    {
        return mb_strtolower($labelName, 'UTF-8');
    }
}
