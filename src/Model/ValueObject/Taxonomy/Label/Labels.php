<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class Labels extends Collection
{
    /**
     * @param Label[] ...$labels
     */
    public function __construct(Label ...$labels)
    {
        // Remove duplicates (with or without the same visibility) by copying every label to a new array, keyed by the
        // label name. If a label name is twice in $labels (with same or different visibility), the last entry will
        // always overwrite the previous entries.
        // Also make sure that the array key is converted to lowercase so we avoid duplicates with other casing. The
        // last casing will be kept, so a previously incorrect casing can be fixed (using with()).
        $uniqueLabels = [];
        foreach ($labels as $label) {
            $key = $label
                ->getName()
                ->toLowerCase()
                ->toString();
            $uniqueLabels[$key] = $label;
        }

        // Remove the keys and make the array sequential (0, 1, 2, ...) to avoid "Cannot unpack array with string keys"
        // error in the parent::__construct() call.
        $uniqueLabels = array_values($uniqueLabels);

        parent::__construct(...$uniqueLabels);
    }

    public function findByName(LabelName $labelName): ?Label
    {
        /** @var Label $label */
        foreach ($this->toArray() as $label) {
            if ($label->getName()->sameAs($labelName)) {
                return $label;
            }
        }
        return null;
    }

    public function findLabel(Label $label): ?Label
    {
        /** @var Label $currentLabel */
        foreach ($this->toArray() as $currentLabel) {
            if ($currentLabel->isVisible() === $label->isVisible() && $currentLabel->getName()->sameAs($label->getName())) {
                return $label;
            }
        }
        return null;
    }

    /**
     * @return Label[]|Labels
     */
    public function getVisibleLabels(): Labels
    {
        return $this->filter(fn (Label $label) => $label->isVisible());
    }

    /**
     * @return Label[]|Labels
     */
    public function getHiddenLabels(): Labels
    {
        return $this->filter(fn (Label $label) => !$label->isVisible());
    }

    public function toArrayOfStringNames(): array
    {
        return array_map(
            fn (Label $label) => $label->getName()->toString(),
            $this->toArray()
        );
    }
}
