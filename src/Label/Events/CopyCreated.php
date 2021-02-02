<?php

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;

final class CopyCreated extends Created
{
    public const PARENT_UUID = 'parentUuid';

    /**
     * @var UUID
     */
    private $parentUuid;

    public function __construct(
        UUID $uuid,
        LabelName $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid
    ) {
        parent::__construct($uuid, $name, $visibility, $privacy);

        $this->parentUuid = $parentUuid;
    }

    public function getParentUuid(): UUID
    {
        return $this->parentUuid;
    }

    public static function deserialize(array $data): CopyCreated
    {
        return new self(
            new UUID($data[self::UUID]),
            new LabelName($data[self::NAME]),
            Visibility::fromNative($data[self::VISIBILITY]),
            Privacy::fromNative($data[self::PRIVACY]),
            new UUID($data[self::PARENT_UUID])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            self::PARENT_UUID => $this->getParentUuid()->toNative(),
        ];
    }
}
