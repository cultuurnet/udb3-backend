<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

abstract class AbstractLabelsImported extends AbstractEvent implements LabelsImportedEventInterface
{
    /**
     * @var string[]
     */
    private array $visibleLabels;

    /**
     * @var string[]
     */
    private array $hiddenLabels;

    final public function __construct(
        string $organizerId,
        array $visibleLabels,
        array $hiddenLabels

    ) {
        parent::__construct($organizerId);
        $this->visibleLabels = $visibleLabels;
        $this->hiddenLabels = $hiddenLabels;
    }

    public function getAllLabelNames(): array
    {
        return array_merge($this->visibleLabels, $this->hiddenLabels);
    }

    public function getVisibleLabelNames(): array
    {
        return $this->visibleLabels;
    }

    public function getHiddenLabelNames(): array
    {
        return $this->hiddenLabels;
    }

    public static function deserialize(array $data): AbstractLabelsImported
    {
        $visibleLabels = [];
        $hiddenLabels = [];
        foreach ($data['labels'] as $label) {
            if ($label['visibility']) {
                $visibleLabels[] = $label['label'];
            } else {
                $hiddenLabels[] = $label['label'];
            }
        }

        return new static(
            $data['item_id'],
            $visibleLabels,
            $hiddenLabels
        );
    }

    public function serialize(): array
    {
        $labels = [];
        foreach ($this->getVisibleLabelNames() as $labelName) {
            $labels[] = [
                'label' => $labelName,
                'visibility' => true,
            ];
        }
        foreach ($this->getHiddenLabelNames() as $labelName) {
            $labels[] = [
                'label' => $labelName,
                'visibility' => false,
            ];
        }

        return parent::serialize() + [
            'labels' => $labels,
        ];
    }
}
