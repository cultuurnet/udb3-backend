<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelEventInterface;

abstract class AbstractLabelEvent extends OrganizerEvent implements LabelEventInterface
{
    /**
     * @var Label
     */
    private $label;

    final public function __construct(
        string $organizerId,
        Label $label
    ) {
        parent::__construct($organizerId);
        $this->label = $label;
    }

    public function getItemId(): string
    {
        return $this->getOrganizerId();
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        return new static(
            $data['organizer_id'],
            new Label(
                $data['label'],
                isset($data['visibility']) ? $data['visibility'] : true
            )
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'label' => (string) $this->label,
            'visibility' => $this->label->isVisible(),
        ];
    }
}
