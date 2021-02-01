<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelEventInterface;

abstract class AbstractLabelEvent extends AbstractEvent implements LabelEventInterface
{
    /**
     * @var Label
     */
    protected $label;

    final public function __construct(string $itemId, Label $label)
    {
        parent::__construct($itemId);
        $this->label = $label;
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function serialize(): array
    {
        return parent::serialize() + array(
            'label' => (string) $this->label,
            'visibility' => $this->label->isVisible(),
        );
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        if (!isset($data['visibility'])) {
            $data['visibility'] = true;
        }

        return new static(
            $data['item_id'],
            new Label($data['label'], $data['visibility'])
        );
    }
}
