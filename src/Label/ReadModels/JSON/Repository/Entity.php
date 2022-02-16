<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use InvalidArgumentException;
use CultuurNet\UDB3\StringLiteral;

class Entity implements \JsonSerializable
{
    public const ID = 'uuid';
    public const NAME = 'name';
    public const VISIBILITY = 'visibility';
    public const PRIVACY = 'privacy';

    /**
     * @var UUID
     */
    private $uuid;

    /**
     * @var StringLiteral
     */
    private $name;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Privacy
     */
    private $privacy;

    /**
     * @var UUID
     */
    private $parentUuid;

    private int $count;

    public function __construct(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null,
        int $count = null
    ) {
        if ($count < 0) {
            throw new InvalidArgumentException('Count should be zero or higher.');
        }

        $this->uuid = $uuid;
        $this->name = $name;
        $this->visibility = $visibility;
        $this->privacy = $privacy;
        $this->parentUuid = $parentUuid;
        $this->count = $count ?: 0;
    }

    /**
     * @return UUID
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return StringLiteral
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Visibility
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return Privacy
     */
    public function getPrivacy()
    {
        return $this->privacy;
    }

    /**
     * @return UUID
     */
    public function getParentUuid()
    {
        return $this->parentUuid;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            self::ID => $this->uuid->toString(),
            self::NAME => $this->name->toNative(),
            self::VISIBILITY => $this->visibility->toString(),
            self::PRIVACY => $this->privacy->toString(),
        ];
    }
}
