<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelEventInterface;

abstract class AbstractLabelEvent extends OrganizerEvent implements LabelEventInterface
{
    private string $labelName;

    private bool $isVisible;

    final public function __construct(
        string $organizerId,
        string $labelName,
        bool   $isVisible = true
    ) {
        parent::__construct($organizerId);
        $this->labelName = $labelName;
        $this->isVisible = $isVisible;
    }

    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }

    public function getLabel(): Label
    {
        return new Label($this->labelName, $this->isVisible);
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        return new static(
            $data['organizer_id'],
            $data['label'],
            $data['visibility'] ?? true,
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'label' => $this->labelName,
                'visibility' => $this->isVisible,
            ];
    }
}
