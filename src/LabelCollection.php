<?php

namespace CultuurNet\UDB3;

use CultureFeed_Cdb_Data_Keyword;

class LabelCollection implements \Countable
{
    /**
     * @var Label[]
     */
    private $labels;

    /**
     * @param Label[] $labels
     */
    public function __construct(array $labels = [])
    {
        array_walk(
            $labels,
            function ($item) {
                if (!$item instanceof Label) {
                    throw new \InvalidArgumentException(
                        'Argument $labels should only contain members of type Label'
                    );
                }
            }
        );

        $this->labels = array_values($labels);
    }

    /**
     * @param Label $label
     * @return LabelCollection
     */
    public function with(Label $label)
    {
        if (!$this->contains($label)) {
            $labels = array_merge($this->labels, [$label]);
        } else {
            $labels = $this->labels;
        }

        return new LabelCollection($labels);
    }

    /**
     * @param Label $label
     * @return LabelCollection
     */
    public function without(Label $label)
    {
        $labels = array_filter(
            $this->labels,
            function (Label $existingLabel) use ($label) {
                return !$existingLabel->equals($label);
            }
        );

        return new LabelCollection($labels);
    }

    /**
     * @inheritdoc
     */
    public function count()
    {
        return count($this->labels);
    }

    /**
     * @param Label $label
     * @return bool
     */
    public function contains(Label $label)
    {
        $equalLabels = array_filter(
            $this->labels,
            function (Label $existingLabel) use ($label) {
                return $label->equals($existingLabel);
            }
        );

        return !empty($equalLabels);
    }

    /**
     * @return Label[]
     */
    public function asArray()
    {
        return $this->labels;
    }

    /**
     * @param LabelCollection $labelCollectionToMerge
     * @return LabelCollection
     */
    public function merge(LabelCollection $labelCollectionToMerge)
    {
        $mergedLabels = [];

        // Keep labels from the original collection that are not in the
        // collection to merge.
        foreach ($this->labels as $label) {
            if (!$labelCollectionToMerge->contains($label)) {
                $mergedLabels[] = $label;
            }
        }

        // Add all labels from the collection to merge.
        $add = $labelCollectionToMerge->asArray();
        $mergedLabels = array_merge($mergedLabels, $add);

        return new LabelCollection($mergedLabels);
    }

    /**
     * @param LabelCollection $labelCollection
     * @return LabelCollection
     */
    public function intersect(LabelCollection $labelCollection)
    {
        $intersectLabels = array_filter(
            $this->labels,
            function ($label) use ($labelCollection) {
                return $labelCollection->contains($label);
            }
        );

        return new LabelCollection($intersectLabels);
    }

    /**
     * @param callable $filterFunction
     * @return LabelCollection
     */
    public function filter(callable $filterFunction)
    {
        return new LabelCollection(
            array_filter($this->labels, $filterFunction)
        );
    }

    /**
     * @param string[] $strings
     * @return LabelCollection
     */
    public static function fromStrings(array $strings)
    {
        $labelCollection = new LabelCollection();

        foreach ($strings as $string) {
            try {
                $label = new Label($string);
                $labelCollection = $labelCollection->with($label);
            } catch (\InvalidArgumentException $exception) {
            }
        }

        return $labelCollection;
    }

    /**
     * @param CultureFeed_Cdb_Data_Keyword[] $keywords
     * @return LabelCollection
     */
    public static function fromKeywords($keywords)
    {
        $labelCollection = new LabelCollection();

        foreach ($keywords as $keyword) {
            try {
                $label = new Label($keyword->getValue(), $keyword->isVisible());
                $labelCollection = $labelCollection->with($label);
            } catch (\InvalidArgumentException $exception) {
            }
        }

        return $labelCollection;
    }

    /**
     * @return string[]
     */
    public function toStrings()
    {
        $labels = array_map(
            function (Label $label) {
                return (string) $label;
            },
            $this->labels
        );

        return $labels;
    }
}
