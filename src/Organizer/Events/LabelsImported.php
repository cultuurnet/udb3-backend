<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\LabelsImportedEventInterface;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

final class LabelsImported extends OrganizerEvent implements LabelsImportedEventInterface
{
    /**
     * @var Labels
     */
    private $labels;

    public function __construct(
        string $organizerId,
        Labels $labels
    ) {
        parent::__construct($organizerId);
        $this->labels = $labels;
    }

    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }


    public function getAllLabelNames(): array
    {
        return $this->labels->toArrayOfStringNames();
    }

    public function getVisibleLabelNames(): array
    {
        return $this->labels->getVisibleLabels()->toArrayOfStringNames();
    }

    public function getHiddenLabelNames(): array
    {
        return $this->labels->getHiddenLabels()->toArrayOfStringNames();
    }

    public static function deserialize(array $data): LabelsImported
    {
        $labels = new Labels();
        foreach ($data['labels'] as $label) {
            $labels = $labels->with(new Label(
                new LabelName($label['label']),
                $label['visibility']
            ));
        }

        return new self(
            $data['organizer_id'],
            $labels
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
