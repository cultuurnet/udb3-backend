<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Events;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class CopyCreated extends Created
{
    public const PARENT_UUID = 'parentUuid';

    private UUID $parentUuid;

    public function __construct(
        UUID $uuid,
        string $name,
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
            $data[self::NAME],
            new Visibility($data[self::VISIBILITY]),
            new Privacy($data[self::PRIVACY]),
            new UUID($data[self::PARENT_UUID])
        );
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            self::PARENT_UUID => $this->getParentUuid()->toString(),
        ];
    }
}
