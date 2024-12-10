<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

abstract class AbstractLabelEvent extends AbstractEvent
{
    public const LABEL_ID = 'labelId';

    private Uuid $labelId;

    final public function __construct(
        Uuid $uuid,
        Uuid $labelId
    ) {
        parent::__construct($uuid);
        $this->labelId = $labelId;
    }

    public function getLabelId(): Uuid
    {
        return $this->labelId;
    }

    public static function deserialize(array $data): AbstractLabelEvent
    {
        return new static(
            new Uuid($data[self::UUID]),
            new Uuid($data[self::LABEL_ID])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [self::LABEL_ID => $this->getLabelId()->toString()];
    }
}
