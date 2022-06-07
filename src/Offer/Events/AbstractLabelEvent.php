<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\LabelEventInterface;

abstract class AbstractLabelEvent extends AbstractEvent implements LabelEventInterface
{
    private string $labelName;

    private bool $isLabelVisible;

    final public function __construct(string $itemId, Label $label)
    {
        parent::__construct($itemId);
        $this->labelName = $label->getName()->toNative();
        $this->isLabelVisible = $label->isVisible();
    }

    public function getLabelName(): string
    {
        return $this->labelName;
    }

    public function isLabelVisible(): bool
    {
        return $this->isLabelVisible;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'label' => $this->labelName,
            'visibility' => $this->isLabelVisible,
        ];
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        if (!isset($data['visibility'])) {
            $data['visibility'] = true;
        }

        return new static(
            $data['item_id'],
            new Label(self::trimLineEndings($data['label']), $data['visibility'])
        );
    }

    private static function trimLineEndings(string $labelName): string
    {
        $labelName = preg_replace('/\\r\\n/', ' ', $labelName);
        return preg_replace('/\\n/', ' ', $labelName);
    }
}
