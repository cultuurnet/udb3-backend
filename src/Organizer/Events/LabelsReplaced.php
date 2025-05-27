<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\LabelsImportedEventInterface;

final class LabelsReplaced extends OrganizerEvent implements LabelsImportedEventInterface
{
    /**
     * @var string[]
     */
    private array $visibleLabels;

    /**
     * @var string[]
     */
    private array $hiddenLabels;

    public function __construct(
        string $organizerId,
        array $visibleLabels,
        array $hiddenLabels
    ) {
        parent::__construct($organizerId);
        $this->visibleLabels = $visibleLabels;
        $this->hiddenLabels = $hiddenLabels;
    }

    public function getItemId(): string
    {
        return $this->getOrganizerId();
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

    public static function deserialize(array $data): LabelsReplaced
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

        return new self(
            $data['organizer_id'],
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
