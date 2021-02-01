<?php

namespace CultuurNet\UDB3\Role\Events;

use ValueObjects\Identity\UUID;

abstract class AbstractLabelEvent extends AbstractEvent
{
    public const LABEL_ID = 'labelId';

    /**
     * @var UUID
     */
    private $labelId;

    final public function __construct(
        UUID $uuid,
        UUID $labelId
    ) {
        parent::__construct($uuid);
        $this->labelId = $labelId;
    }

    public function getLabelId(): UUID
    {
        return $this->labelId;
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        return new static(
            new UUID($data[self::UUID]),
            new UUID($data[self::LABEL_ID])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [self::LABEL_ID => $this->getLabelId()->toNative()];
    }
}
