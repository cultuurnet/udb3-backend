<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

abstract class AbstractLabelsImported extends AbstractEvent implements LabelsImportedEventInterface
{
    /**
     * @var Labels
     */
    private $labels;

    final public function __construct(
        string $organizerId,
        Labels $labels
    ) {
        parent::__construct($organizerId);
        $this->labels = $labels;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    public static function deserialize(array $data): AbstractLabelsImported
    {
        $labels = new Labels();
        foreach ($data['labels'] as $label) {
            $labels = $labels->with(new Label(
                new LabelName($label['label']),
                $label['visibility']
            ));
        }

        return new static(
            $data['item_id'],
            $labels
        );
    }

    public function serialize(): array
    {
        $labels = [];
        foreach ($this->getLabels() as $label) {
            /** @var Label $label */
            $labels[] = [
                'label' => $label->getName()->toString(),
                'visibility' => $label->isVisible(),
            ];
        }

        return parent::serialize() + [
            'labels' => $labels,
        ];
    }
}
